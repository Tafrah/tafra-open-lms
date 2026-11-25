<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Installer/Tests/helpers.php';

use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Demo\Application\DemoMode;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

/*
| نسخة التجربة: `lms:demo` بيبني أكاديمية ببيانات، وصفحات الدخول فيها «ادخل كـ…».
| الأمان: الدخول بضغطة محتاج DEMO_MODE **و** تثبيت معمول بـ lms:demo.
*/

beforeEach(function (): void {
    $this->dir = freshInstallerSandbox();
    config(['demo.enabled' => true]);
});

function markDemoInstall(bool $demo = true): void
{
    app(InstallationState::class)->markInstalled($demo ? ['demo' => true] : []);
}

it('lms:demo بيرفض من غير DEMO_MODE', function (): void {
    config(['demo.enabled' => false]);

    $this->artisan('lms:demo')->assertFailed();

    expect(app(InstallationState::class)->isInstalled())->toBeFalse();
});

it('lms:demo مابيلمسش منصة متثبّتة بجد — حتى مع --fresh', function (): void {
    markDemoInstall(demo: false);

    $this->artisan('lms:demo', ['--fresh' => true])->assertFailed();
    $this->artisan('lms:demo', ['--if-not-installed' => true])->assertFailed();
});

it('lms:demo بيثبّت ويبني بيانات أكاديمية كاملة', function (): void {
    $this->artisan('lms:demo')->assertSuccessful();

    $demo = app(DemoMode::class);
    inTenant(Tenant::query()->orderBy('id')->firstOrFail());

    expect($demo->active())->toBeTrue()
        ->and(User::query()->where('email', 'teacher@demo.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'assistant@demo.test')->exists())->toBeTrue()
        ->and(Student::query()->where('phone', '01000000000')->exists())->toBeTrue()
        ->and(Student::query()->count())->toBeGreaterThan(15)
        ->and(PaymentInvoice::query()->where('status', InvoiceStatus::Pending)->count())->toBe(4)
        ->and(PaymentInvoice::query()->where('status', InvoiceStatus::Paid)->count())->toBeGreaterThan(5)
        ->and(OnlinePassedExam::query()->where('finished', true)->count())->toBeGreaterThan(10);

    // مرة تانية من غير --fresh: مابيكرّرش البيانات
    $this->artisan('lms:demo', ['--if-not-installed' => true])->assertSuccessful();
    $this->artisan('lms:demo')->assertFailed();
});

it('الدخول بضغطة 404 لو المنصة مش نسخة تجربة', function (bool $enabled, bool $demoInstall): void {
    config(['demo.enabled' => $enabled]);
    markDemoInstall($demoInstall);
    userWithRole('admin')->forceFill(['email' => 'teacher@demo.test'])->save();

    $this->post(route('demo.login.staff', 'teacher'))->assertNotFound();
    $this->post(route('demo.login.student'))->assertNotFound();
    $this->assertGuest('web');

    $this->get('/admin/login')->assertDontSee('data-demo-login', false);
})->with([
    'DEMO_MODE مقفول' => [false, true],
    'تثبيت حقيقي + DEMO_MODE بالغلط' => [true, false],
]);

it('الدخول بضغطة للمدرس والمساعد والطالب', function (): void {
    markDemoInstall();
    $tenant = inTenant();

    $teacher = userWithRole('admin', $tenant);
    $teacher->forceFill(['email' => 'teacher@demo.test'])->save();
    $student = Student::factory()->create(['phone' => '01000000000']);

    $this->get('/admin/login')->assertSee('data-demo-login="teacher"', false);
    $this->get(route('site.login'))->assertSee('data-demo-login="student"', false);

    $this->post(route('demo.login.staff', 'teacher'))->assertRedirect();
    $this->assertAuthenticatedAs($teacher, 'web');

    $this->post(route('demo.login.student'))->assertRedirect(route('site.dashboard'));
    $this->assertAuthenticatedAs($student, 'student');
});

it('مفيش دخول لحساب مش في قايمة التجربة', function (): void {
    markDemoInstall();

    $this->post('/demo/login/owner')->assertNotFound();
    // الحساب في القايمة بس مش موجود في القاعدة
    $this->post(route('demo.login.staff', 'viewer'))->assertNotFound();
});
