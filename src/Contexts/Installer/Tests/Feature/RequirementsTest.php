<?php

declare(strict_types=1);

use Src\Contexts\Installer\Application\Actions\CheckRequirementsAction;

require_once __DIR__.'/../helpers.php';

beforeEach(function (): void {
    freshInstallerSandbox();
});

it('بيعدّي على الجهاز ده — PHP 8.4 وكل الإضافات موجودة', function (): void {
    $report = app(CheckRequirementsAction::class)->handle();

    expect($report->passes())->toBeTrue()
        ->and($report->failures())->toBeEmpty()
        ->and(array_keys($report->grouped()))->toContain('php', 'extensions', 'permissions', 'recommended');
});

it('إضافة ناقصة بتمنع التثبيت', function (): void {
    config(['installer.requirements.extensions' => ['mbstring', 'definitely_not_an_extension']]);

    $report = app(CheckRequirementsAction::class)->handle();

    expect($report->passes())->toBeFalse()
        ->and(array_map(static fn ($check): string => $check->label, $report->failures()))->toBe(['definitely_not_an_extension']);
});

it('إصدار PHP أقل من المطلوب بيمنع التثبيت', function (): void {
    config(['installer.requirements.php' => '99.0.0']);

    expect(app(CheckRequirementsAction::class)->handle()->passes())->toBeFalse();
});

it('مجموعة «واحد من» بتعدّي لو أي واحد منهم موجود', function (): void {
    config(['installer.requirements.one_of' => ['images' => ['not_here', 'mbstring']]]);

    expect(app(CheckRequirementsAction::class)->handle()->passes())->toBeTrue();
});

it('التحذيرات مابتمنعش التثبيت', function (): void {
    $report = app(CheckRequirementsAction::class)->handle();

    foreach ($report->grouped()['recommended'] as $check) {
        expect($check->blocking)->toBeFalse();
    }
});

it('صفحة المتطلبات بتترسم بالعربي واتجاه RTL', function (): void {
    $this->get('/install/requirements')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee(__('installer::installer.requirements.title'))
        ->assertSee('/install/database', false);
});

it('صفحة المتطلبات بالإنجليزي بعد تبديل اللغة', function (): void {
    $this->get('/install/lang/en')->assertRedirect();

    $this->get('/install/requirements')
        ->assertOk()
        ->assertSee('dir="ltr"', false)
        ->assertSee('Hosting check');
});

it('متطلبات ناقصة = مفيش زرار «كمّل» وخطوة القاعدة بترجّع للمتطلبات', function (): void {
    config(['installer.requirements.php' => '99.0.0']);

    $this->get('/install/requirements')->assertOk()->assertDontSee('/install/database', false);
    $this->get('/install/database')->assertRedirect('/install/requirements');
});
