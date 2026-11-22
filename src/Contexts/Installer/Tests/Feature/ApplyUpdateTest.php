<?php

declare(strict_types=1);

require_once __DIR__.'/../helpers.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Src\Contexts\Installer\Application\Actions\ApplyUpdateAction;
use Src\Contexts\Installer\Infrastructure\InstallationState;

/*
| تحديث من غير SSH: المدرس بيرفع zip إصدار جديد، وأول طلب بيطبّق الميجريشنز.
*/

function installedAt(string $dir, string $installed, string $uploaded): void
{
    File::put($dir.'/installed.json', json_encode(['version' => $installed, 'installed_at' => now()->toIso8601String()]));
    File::put($dir.'/VERSION', $uploaded);
    app(InstallationState::class)->forget();
}

it('أول طلب بعد رفع إصدار جديد بيطبّق التحديث ويسجّل الإصدار', function (): void {
    $dir = freshInstallerSandbox();
    installedAt($dir, '1.0.0', '1.1.0');

    Artisan::spy();

    $this->get('/up')->assertOk(); // فحص الصحة مابيشغّلش التحديث
    expect(app(ApplyUpdateAction::class)->needed())->toBeTrue();

    $this->get('/');

    Artisan::shouldHaveReceived('call')->with('migrate', ['--force' => true])->once();
    Artisan::shouldHaveReceived('call')->with('authorization:sync')->once();

    $marker = app(InstallationState::class)->marker();

    expect($marker['version'])->toBe('1.1.0')
        ->and($marker['updated_from'])->toBe('1.0.0')
        ->and($marker['installed_at'])->not->toBeNull()
        ->and(app(ApplyUpdateAction::class)->needed())->toBeFalse();
});

it('نفس الإصدار = مفيش تحديث', function (): void {
    $dir = freshInstallerSandbox();
    installedAt($dir, '1.1.0', '1.1.0');

    expect(app(ApplyUpdateAction::class)->needed())->toBeFalse()
        ->and(app(ApplyUpdateAction::class)->handle())->toBe(ApplyUpdateAction::NOT_NEEDED);
});

it('طلب تاني وقت التحديث بياخد صفحة «جاري التحديث» مش خطأ', function (): void {
    $dir = freshInstallerSandbox();
    installedAt($dir, '1.0.0', '1.1.0');

    $handle = fopen(storage_path('framework/update.lock'), 'c');
    flock($handle, LOCK_EX);

    try {
        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '30')
            ->assertSee(__('installer::installer.update.busy.title'));
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
});

it('تحديث فشل بيوري صفحة واضحة ومايسجّلش الإصدار الجديد', function (): void {
    $dir = freshInstallerSandbox();
    installedAt($dir, '1.0.0', '1.1.0');

    Artisan::shouldReceive('call')->with('migrate', ['--force' => true])->andThrow(new RuntimeException('boom'));

    $this->get('/')
        ->assertStatus(500)
        ->assertSee(__('installer::installer.update.failed.title'));

    expect(app(InstallationState::class)->marker()['version'])->toBe('1.0.0');
});
