<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserDevice;
use Src\Contexts\Identity\Presentation\Filament\Pages\MyDevices;
use Src\Contexts\Identity\Presentation\Filament\Pages\TwoFactorSettings;

/*
|--------------------------------------------------------------------------
| معايير قبول صفحات الحساب: أجهزتي + التحقق بخطوتين (docs/12 بند ١ و٢)
|--------------------------------------------------------------------------
| الصفحتين في اللوحتين — المستخدم بيدير أمان حسابه من أي مكان هو فيه.
*/

function deviceFor(User $user, string $session): UserDevice
{
    return UserDevice::query()->create([
        'user_id' => $user->getKey(),
        'session_id' => $session,
        'fingerprint' => hash('sha256', $session),
        'device_name' => 'Firefox على Linux',
        'ip_address' => '10.0.0.1',
        'last_active_at' => now(),
    ]);
}

it('صفحات الحساب بتفتح في اللوحتين', function (string $page, string $panel): void {
    $panel === 'central' ? actingAsCentral('support') : actingAsRole('viewer');

    $this->get($page::getUrl(panel: $panel))->assertOk();
})->with([MyDevices::class, TwoFactorSettings::class])->with(['admin', 'central']);

it('أجهزتي بتعرض أجهزة المستخدم بس', function (): void {
    $user = actingAsRole('viewer');

    $mine = deviceFor($user, 'session-mine');
    $other = deviceFor(userWithRole('viewer'), 'session-other');

    Livewire::test(MyDevices::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other]);
});

it('إنهاء جهاز من الشاشة بيشيله', function (): void {
    $user = actingAsRole('viewer');

    $device = deviceFor($user, 'session-to-end');

    Livewire::test(MyDevices::class)
        ->callTableAction('terminate', $device)
        ->assertNotified(__('identity::identity.device.notifications.terminated'));

    expect(UserDevice::query()->whereKey($device->getKey())->exists())->toBeFalse();
});

it('تفعيل التحقق بخطوتين بيبدأ بمرحلة التأكيد مش بتفعيل مباشر', function (): void {
    $user = actingAsRole('viewer');

    Livewire::test(TwoFactorSettings::class)
        ->callAction('enable')
        ->assertActionVisible('confirm')
        ->assertActionHidden('enable');

    $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

it('كود غلط مابيفعّلش التحقق بخطوتين', function (): void {
    $user = actingAsRole('viewer');

    Livewire::test(TwoFactorSettings::class)
        ->callAction('enable')
        ->callAction('confirm', ['code' => '000000'])
        ->assertNotified(__('identity::identity.two_factor.notifications.invalid_code'));

    expect($user->refresh()->two_factor_confirmed_at)->toBeNull();
});
