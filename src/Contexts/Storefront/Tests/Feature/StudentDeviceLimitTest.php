<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Illuminate\Testing\TestResponse;
use Src\Contexts\Storefront\Presentation\Support\StudentDeviceSession;
use Src\Contexts\Students\Application\Actions\ManageStudentAction;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Domain\Models\StudentDevice;

/*
|--------------------------------------------------------------------------
| «جهاز واحد لكل طالب» (docs/product/plan.html#student)
|--------------------------------------------------------------------------
| كل جهاز = توكن في كوكي `sf_device`. الجلسة في الاختبارات بتفضل بين الطلبات،
| فـ «جهاز تاني» = جلسة جديدة من غير الكوكي.
*/

beforeEach(fn () => inTenant());

const DEVICE_UA_PHONE = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36';

function devLogin(Student $student, ?string $cookie = null): TestResponse
{
    $test = test()->withHeader('User-Agent', DEVICE_UA_PHONE);

    if ($cookie !== null) {
        $test = $test->withCookie((string) config('storefront.devices.cookie'), $cookie);
    }

    return $test->post(route('site.login.store'), ['phone' => $student->phone, 'password' => 'secret-pass']);
}

/** جهاز تاني تمامًا: جلسة جديدة ومفيش كوكي */
function devNewBrowser(): void
{
    auth('student')->logout();
    test()->flushSession();
}

function devToken(): string
{
    return (string) session(StudentDeviceSession::SESSION_KEY.'.token');
}

it('الدخول بيسجّل الجهاز (hash بس) ويبعت كوكي الجهاز', function (): void {
    $student = sfStudent(['password' => 'secret-pass']);

    $response = devLogin($student)->assertRedirect(route('site.dashboard'));

    $device = StudentDevice::query()->sole();
    $token = devToken();

    expect($device->student_id)->toBe($student->getKey())
        ->and($device->token_hash)->toBe(StudentDevice::hashToken($token))
        ->and($device->token_hash)->not->toBe($token)
        ->and($device->device_name)->toBe('Chrome · Android')
        ->and($device->last_seen_at)->not->toBeNull();
    $response->assertCookie((string) config('storefront.devices.cookie'), $token);
});

it('جهاز تاني بيترفض برسالة «كلّم المدرس» لما الحد جهاز واحد', function (): void {
    $student = sfStudent(['password' => 'secret-pass']);
    devLogin($student);
    devNewBrowser();

    devLogin($student)->assertSessionHasErrors(['phone' => __('storefront::storefront.errors.device_limit', ['count' => fc_number(1)])]);

    $this->assertGuest('student');
    expect(StudentDevice::query()->count())->toBe(1);
});

it('نفس الجهاز (نفس الكوكي) بيدخل تاني عادي', function (): void {
    $student = sfStudent(['password' => 'secret-pass']);
    devLogin($student);
    $token = devToken();
    devNewBrowser();

    devLogin($student, $token)->assertRedirect(route('site.dashboard'));

    $this->assertAuthenticatedAs($student, 'student');
    expect(StudentDevice::query()->count())->toBe(1);
});

it('المدرس بيحدد العدد: ٢ = جهازين، 0 = من غير حد', function (): void {
    $student = sfStudent(['password' => 'secret-pass']);
    sfSiteSettings(['max_devices_per_student' => 2]);

    devLogin($student)->assertRedirect();
    devNewBrowser();
    devLogin($student)->assertRedirect(route('site.dashboard'));
    devNewBrowser();
    devLogin($student)->assertSessionHasErrors('phone');

    sfSiteSettings(['max_devices_per_student' => 0]);
    devNewBrowser();
    devLogin($student)->assertRedirect(route('site.dashboard'));

    expect(StudentDevice::query()->count())->toBe(3);
});

it('الحد لكل طالب — جهاز طالب تاني مابيتحسبش عليه', function (): void {
    $first = sfStudent(['password' => 'secret-pass']);
    $second = sfStudent(['password' => 'secret-pass']);

    devLogin($first);
    devNewBrowser();
    devLogin($second)->assertRedirect(route('site.dashboard'));

    expect($first->devices()->count())->toBe(1)->and($second->devices()->count())->toBe(1);
});

it('فصل الأجهزة من اللوحة بيطلّع الطالب في أول طلب، وجهاز جديد يقدر يدخل بعدها', function (): void {
    $admin = userWithRole('admin');
    $student = sfStudent(['password' => 'secret-pass']);
    devLogin($student);
    $this->get(route('site.dashboard'))->assertOk();

    app(ManageStudentAction::class)->disconnectDevices($student, $admin);

    $this->get(route('site.dashboard'))
        ->assertRedirect(route('site.login'))
        ->assertSessionHasErrors(['phone' => __('storefront::storefront.errors.device_disconnected')]);
    $this->assertGuest('student');

    devNewBrowser();
    devLogin($student)->assertRedirect(route('site.dashboard'));
    expect($student->devices()->count())->toBe(1);
});

it('جلسة من غير جهاز («افتكرني» أو أقدم من الميزة) بتتسجّل بنفس القواعد', function (): void {
    $student = sfStudent();
    StudentDevice::query()->create(['student_id' => $student->getKey(), 'token_hash' => StudentDevice::hashToken(str_repeat('a', 40))]);

    sfLogin($student);

    $this->get(route('site.dashboard'))
        ->assertRedirect(route('site.login'))
        ->assertSessionHasErrors('phone');
    $this->assertGuest('student');
});

it('«آخر ظهور» بيتحدّث بس بعد المهلة — مش مع كل طلب', function (): void {
    $student = sfStudent(['password' => 'secret-pass']);
    devLogin($student);
    $device = StudentDevice::query()->sole();

    $this->travel(1)->minutes();
    $this->get(route('site.dashboard'))->assertOk();
    expect($device->fresh()->last_seen_at->equalTo($device->last_seen_at))->toBeTrue();

    $this->travel(10)->minutes();
    $this->get(route('site.dashboard'))->assertOk();
    expect($device->fresh()->last_seen_at->gt($device->last_seen_at))->toBeTrue();
});
