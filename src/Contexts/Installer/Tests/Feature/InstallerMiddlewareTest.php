<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;

require_once __DIR__.'/../helpers.php';

it('الاختبارات بتعتبر التطبيق متثبّت افتراضياً', function (): void {
    expect(app(InstallationState::class)->isInstalled())->toBeTrue();

    $this->get('/install')->assertNotFound();
});

describe('قبل التثبيت', function (): void {
    beforeEach(function (): void {
        freshInstallerSandbox();
    });

    it('أي صفحة بتتحوّل لـ /install بتحويل نسبي', function (string $path): void {
        $response = $this->get($path);

        $response->assertRedirect('/install');
        expect($response->headers->get('Location'))->toBe('/install');
    })->with(['/', '/admin', '/admin/login', '/central']);

    it('صفحات المثبّت نفسها مابتتحوّلش', function (): void {
        $this->get('/install')->assertRedirect('/install/requirements');
        $this->get('/install/requirements')->assertOk();
    });

    it('فحص الصحة مابيتحوّلش', function (): void {
        $this->get('/up')->assertOk();
    });

    it('النماذج فيها توكن CSRF', function (): void {
        $this->get('/install/requirements');

        $this->get('/install/database')->assertOk()->assertSee('name="_token"', false);
    });

    it('خطوة المنصة من غير قاعدة محفوظة بترجع لخطوة القاعدة', function (): void {
        $this->get('/install/academy')->assertRedirect('/install/database');
        $this->post('/install/academy', academyInput(['admin_password_confirmation' => 'Secret123']))
            ->assertRedirect('/install/database');
    });
});

describe('بعد التثبيت', function (): void {
    beforeEach(function (): void {
        $dir = freshInstallerSandbox();
        file_put_contents($dir.'/installed.json', '{"version":"1.0.0"}');
    });

    it('كل مسارات المثبّت 404', function (string $method, string $path): void {
        $this->call($method, $path)->assertNotFound();
    })->with([
        ['GET', '/install'],
        ['GET', '/install/requirements'],
        ['GET', '/install/database'],
        ['POST', '/install/database'],
        ['GET', '/install/academy'],
        ['POST', '/install/academy'],
        ['GET', '/install/lang/en'],
    ]);

    it('الصفحات العادية مابتتحوّلش', function (): void {
        $this->get('/admin/login')->assertOk();
    });
});

it('تثبيت قديم (وضع مقفول من deploy.sh) بيتعتبر متثبّت وبيكتب العلامة', function (): void {
    $dir = freshInstallerSandbox();

    app(TenancyModeLock::class)->lock('single');

    expect(app(InstallationState::class)->isInstalled())->toBeTrue()
        ->and(is_file($dir.'/installed.json'))->toBeTrue()
        ->and(json_decode((string) file_get_contents($dir.'/installed.json'), true))->toHaveKey('legacy', true);

    DB::table('system_state')->where('key', 'tenancy.mode')->delete();
});
