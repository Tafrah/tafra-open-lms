<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Notification;
use Src\Contexts\Identity\Application\Actions\ForceLogoutAction;
use Src\Contexts\Identity\Domain\Events\DeviceRegistered;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserDevice;
use Src\Contexts\Identity\Domain\Models\UserPasswordHistory;
use Src\Contexts\Identity\Infrastructure\Listeners\NotifyOfNewDevice;
use Src\Contexts\Identity\Infrastructure\Notifications\NewDeviceLoginNotification;
use Src\Contexts\Identity\Infrastructure\Notifications\PasswordChangedNotification;
use Src\Contexts\Identity\Infrastructure\Rules\NotARecentPassword;
use Src\Contexts\Settings\Domain\Settings\AppearanceSettings;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;

/*
|--------------------------------------------------------------------------
| الأجهزة
|--------------------------------------------------------------------------
*/

it('يسجّل الجهاز عند الدخول ويبعت تنبيه أول مرة بس', function (): void {
    Notification::fake();

    $user = userWithRole('admin');

    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0')
        ->actingAs($user);

    event(new Login('web', $user, false));

    expect(UserDevice::query()->where('user_id', $user->getKey())->count())->toBe(1);

    // الدخول التاني من نفس الجهاز مايبعتش تنبيه تاني
    event(new Login('web', $user, false));

    expect(UserDevice::query()->where('user_id', $user->getKey())->count())->toBe(1);

    Notification::assertSentToTimes($user, NewDeviceLoginNotification::class, 1);
});

it('إنهاء جهاز واحد ما يمسّش باقي الأجهزة', function (): void {
    $user = userWithRole('admin');

    $kept = UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'session_id' => 'session-kept',
        'fingerprint' => hash('sha256', 'session-kept'),
        'device_name' => 'Firefox على Linux',
        'ip_address' => '10.0.0.1',
        'last_active_at' => now(),
    ]);

    $doomed = UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'session_id' => 'session-doomed',
        'fingerprint' => hash('sha256', 'session-doomed'),
        'device_name' => 'Chrome على Windows',
        'ip_address' => '10.0.0.2',
        'last_active_at' => now(),
    ]);

    expect(app(ForceLogoutAction::class)->device($user, $doomed))->toBeTrue();

    // ⚠️ الباگ اللي الاختبار ده موجود عشانه: الزرار كان بينادي `handle()`
    // اللي بتنهي **كل** الجلسات ما عدا الحالية — يعني إنهاء جهاز مشبوه
    // كان بيطلّع المستخدم من كل أجهزته.
    expect(UserDevice::query()->whereKey($doomed->getKey())->exists())->toBeFalse()
        ->and(UserDevice::query()->whereKey($kept->getKey())->exists())->toBeTrue()
        ->and($kept->fresh()?->session_id)->toBe('session-kept');
});

it('يرفض إنهاء جهاز مش بتاع المستخدم', function (): void {
    $owner = userWithRole('admin');
    $other = userWithRole('viewer');

    $device = UserDevice::query()->create([
        'user_id' => $other->getKey(),
        'session_id' => 'session-other',
        'fingerprint' => hash('sha256', 'session-other'),
        'device_name' => 'Safari على macOS',
        'ip_address' => '10.0.0.3',
        'last_active_at' => now(),
    ]);

    expect(app(ForceLogoutAction::class)->device($owner, $device))->toBeFalse()
        ->and(UserDevice::query()->whereKey($device->getKey())->exists())->toBeTrue();
});

it('يبعت تنبيه الجهاز الجديد عبر المستمع', function (): void {
    Notification::fake();

    $user = userWithRole('admin');

    app(NotifyOfNewDevice::class)->handle(new DeviceRegistered(
        userId: (int) $user->getKey(),
        deviceId: 1,
        deviceName: 'Chrome · Windows',
        ipAddress: '127.0.0.1',
    ));

    Notification::assertSentTo($user, NewDeviceLoginNotification::class);
});

/*
|--------------------------------------------------------------------------
| كلمات المرور
|--------------------------------------------------------------------------
*/

it('يسجّل الهاش القديم ويبعت تنبيه عند تغيير كلمة المرور', function (): void {
    Notification::fake();

    $user = userWithRole('admin');

    $user->forceFill(['password' => 'New!Password#2026'])->save();

    expect(UserPasswordHistory::query()->where('user_id', $user->getKey())->count())->toBe(1)
        ->and($user->fresh()->password_changed_at)->not->toBeNull();

    Notification::assertSentTo($user, PasswordChangedNotification::class);
});

it('يمنع إعادة استخدام كلمة مرور قديمة', function (): void {
    $user = userWithRole('admin');

    // كلمة المرور الحالية من الـ factory هي 'password'
    $failed = null;

    (new NotARecentPassword($user))->validate(
        'password',
        'password',
        function (string $message) use (&$failed): void {
            $failed = $message;
        },
    );

    expect($failed)->not->toBeNull();
});

it('يقبل كلمة مرور جديدة تماماً', function (): void {
    $user = userWithRole('admin');
    $failed = null;

    (new NotARecentPassword($user))->validate(
        'password',
        'Totally#Different!2026',
        function (string $message) use (&$failed): void {
            $failed = $message;
        },
    );

    expect($failed)->toBeNull();
});

it('يقصّ تاريخ كلمات المرور على الحد المحدد', function (): void {
    Notification::fake();

    // الإعداد جاي من قاعدة البيانات مش من الكونفيج — لازم نغيّره هناك
    $settings = app(SecuritySettings::class);
    $settings->password_history_size = 2;
    $settings->save();

    $user = userWithRole('admin');

    foreach (['A#aaa1111!', 'B#bbbb222!', 'C#cccc333!', 'D#dddd444!'] as $password) {
        $user->forceFill(['password' => $password])->save();
    }

    expect(UserPasswordHistory::query()->where('user_id', $user->getKey())->count())
        ->toBeLessThanOrEqual(2);
});

/*
|--------------------------------------------------------------------------
| لون العلامة لكل مؤسسة
|--------------------------------------------------------------------------
*/

it('يستخدم لون المؤسسة الحالية قبل الإعداد العام', function (): void {
    $tenant = currentTenant();
    $tenant->forceFill(['primary_color' => '#A32E3C'])->save();

    actingAsRole('admin', $tenant);

    $resolver = app(ThemeColorResolver::class);

    expect($resolver->primaryHex())->toBe('#A32E3C')
        ->and($resolver->scale()[600])->toBe('#A32E3C');
});

it('يعطي كل مؤسسة لوحة ألوان مختلفة', function (): void {
    $a = currentTenant();
    $a->forceFill(['primary_color' => '#12454F'])->save();

    $b = Tenant::factory()->create(['primary_color' => '#2E5AAC']);

    $user = User::factory()->create();
    $user->tenants()->syncWithoutDetaching([$a->getKey(), $b->getKey()]);
    $this->actingAs($user);

    Filament::setCurrentPanel('admin');

    Filament::setTenant($a, isQuiet: true);
    $paletteA = app(ThemeColorResolver::class)->scale();

    Filament::setTenant($b, isQuiet: true);
    $paletteB = app(ThemeColorResolver::class)->scale();

    expect($paletteA[600])->toBe('#12454F')
        ->and($paletteB[600])->toBe('#2E5AAC')
        ->and($paletteA[700])->not->toBe($paletteB[700]);
});

it('يرجع للإعداد العام لو المؤسسة مالهاش لون صالح', function (): void {
    $tenant = currentTenant();
    $tenant->forceFill(['primary_color' => 'zzz'])->save();

    actingAsRole('admin', $tenant);

    expect(app(ThemeColorResolver::class)->primaryHex())
        ->toBe(app(AppearanceSettings::class)->primary_color);
});
