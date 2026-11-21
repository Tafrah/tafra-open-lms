<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Src\Contexts\Identity\Application\Actions\ExportUserDataAction;
use Src\Contexts\Identity\Application\Actions\ForgetUserAction;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserDevice;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/*
|--------------------------------------------------------------------------
| حق التصدير وحق الحذف (docs/20 بند ١٣)
|--------------------------------------------------------------------------
*/

it('يصدّر بيانات المستخدم من غير أسرار', function (): void {
    $user = userWithRole('admin');

    UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'session_id' => 'export-session',
        'fingerprint' => hash('sha256', 'export'),
        'device_name' => 'Chrome',
        'ip_address' => '10.0.0.9',
        'last_active_at' => now(),
    ]);

    $data = app(ExportUserDataAction::class)->handle($user);

    expect($data['user']['email'])->toBe($user->email)
        ->and($data['user'])->not->toHaveKey('password')
        ->and($data['user'])->not->toHaveKey('remember_token')
        ->and($data['user_devices'])->toHaveCount(1)
        ->and($data)->toHaveKeys(['tenant_user', 'notification_preferences', 'activity_log']);
});

it('يمسح بيانات المستخدم ويجهّل سجل التدقيق بدل ما يحذفه', function (): void {
    $user = userWithRole('admin');
    $id = $user->getKey();
    $email = $user->email;

    activity('security')->causedBy($user)->log('force_logout');

    UserDevice::query()->create([
        'user_id' => $id,
        'session_id' => 'forget-session',
        'fingerprint' => hash('sha256', 'forget'),
        'device_name' => 'Chrome',
        'ip_address' => '10.0.0.10',
        'last_active_at' => now(),
    ]);

    // الجداول دي مركزية — في وضع multi الاتصال الافتراضي بيبقى قاعدة المؤسسة
    $central = DB::connection(TenancyMode::centralConnection());

    $before = $central->table('activity_log')->where('causer_id', $id)->count();
    expect($before)->toBeGreaterThan(0);

    app(ForgetUserAction::class)->handle($user);

    $fresh = User::withTrashed()->find($id);

    expect(UserDevice::query()->where('user_id', $id)->count())->toBe(0)
        ->and($central->table('tenant_user')->where('user_id', $id)->count())->toBe(0)
        // السجل اتجهّل — موجود من غير هوية
        ->and($central->table('activity_log')->where('causer_id', $id)->count())->toBe(0)
        ->and($central->table('activity_log')->count())->toBeGreaterThan(0)
        // المستخدم نفسه اتجهّل واتحذف ناعم
        ->and($fresh?->trashed())->toBeTrue()
        ->and($fresh?->email)->not->toBe($email)
        ->and($fresh?->is_active)->toBeFalse();
});
