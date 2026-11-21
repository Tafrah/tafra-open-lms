<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Illuminate\Support\Facades\Hash;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Students\Domain\Models\Student;

beforeEach(fn () => inTenant());

it('التسجيل بيعمل الحساب ويدخّله (والأرقام العربي بتتطبّع)', function (): void {
    $level = EducationalLevel::factory()->create();

    $this->post(route('site.register.store'), [
        'name' => 'Ahmed Mohamed Ali Hassan',
        'phone' => '٠١٠١٢٣٤٥٦٧٨',
        'parent_phone' => '01112345678',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
        'educational_level_id' => $level->getKey(),
        'governorate' => 'cairo',
        'school_type' => 'general',
    ])->assertRedirect(route('site.dashboard'));

    $student = Student::query()->where('phone', '01012345678')->first();

    expect($student)->not->toBeNull()
        ->and($student->educational_level_id)->toBe($level->getKey())
        ->and($student->governorate)->toBe('cairo');
    $this->assertAuthenticatedAs($student, 'student');
});

it('التسجيل بيرفض رقم مش مصري وكلمة سر قصيرة وصف مش موجود', function (): void {
    $this->post(route('site.register.store'), [
        'name' => 'Ali',
        'phone' => '01312345678',
        'password' => 'short',
        'password_confirmation' => 'short',
        'educational_level_id' => 999999,
        'governorate' => 'cairo',
        'school_type' => 'general',
    ])->assertSessionHasErrors(['phone', 'password', 'educational_level_id']);

    expect(Student::query()->count())->toBe(0);
});

it('رقم متسجل قبل كده بيرجع غلطة على الموبايل', function (): void {
    $level = EducationalLevel::factory()->create();
    sfStudent(['phone' => '01012345678']);

    $this->post(route('site.register.store'), [
        'name' => 'Someone Else',
        'phone' => '01012345678',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
        'educational_level_id' => $level->getKey(),
        'governorate' => 'giza',
        'school_type' => 'azhar',
    ])->assertSessionHasErrors('phone');
});

it('موبايل ولي الأمر بيبقى إجباري لو المدرس طلب كده', function (): void {
    $level = EducationalLevel::factory()->create();
    sfSiteSettings(['parent_phone_required' => true, 'ask_governorate' => false, 'ask_school_type' => false]);

    $this->post(route('site.register.store'), [
        'name' => 'Ahmed Mohamed Ali',
        'phone' => '01012345678',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
        'educational_level_id' => $level->getKey(),
    ])->assertSessionHasErrors('parent_phone');
});

it('الدخول بالموبايل وكلمة السر', function (): void {
    $student = sfStudent(['phone' => '01012345678', 'password' => 'secret-pass']);

    $this->post(route('site.login.store'), ['phone' => '+20 101 234 5678', 'password' => 'secret-pass'])
        ->assertRedirect(route('site.dashboard'));

    $this->assertAuthenticatedAs($student, 'student');
    expect($student->fresh()->last_login_at)->not->toBeNull();
});

it('كلمة سر غلط بترفض من غير ما تقول الرقم موجود ولا لأ', function (): void {
    sfStudent(['phone' => '01012345678', 'password' => 'secret-pass']);

    $wrong = $this->post(route('site.login.store'), ['phone' => '01012345678', 'password' => 'nope-nope']);
    $unknown = $this->post(route('site.login.store'), ['phone' => '01299999999', 'password' => 'nope-nope']);

    $wrong->assertSessionHasErrors(['phone' => __('storefront::storefront.errors.invalid_credentials')]);
    $unknown->assertSessionHasErrors(['phone' => __('storefront::storefront.errors.invalid_credentials')]);
    $this->assertGuest('student');
});

it('الطالب الموقوف مايقدرش يدخل حتى بكلمة سر صح', function (): void {
    sfStudent(['phone' => '01012345678', 'password' => 'secret-pass', 'status' => 'blocked']);

    $this->post(route('site.login.store'), ['phone' => '01012345678', 'password' => 'secret-pass'])
        ->assertSessionHasErrors(['phone' => __('storefront::storefront.errors.blocked')]);

    $this->assertGuest('student');
});

it('الطالب اللي اتوقف وهو داخل بيتطلع برا', function (): void {
    $student = sfLogin();
    $student->forceFill(['status' => 'blocked'])->save();

    $this->get(route('site.dashboard'))->assertRedirect(route('site.login'));
});

it('بعد ٥ محاولات غلط الدخول بيتقفل حتى بكلمة السر الصح', function (): void {
    sfStudent(['phone' => '01012345678', 'password' => 'secret-pass']);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('site.login.store'), ['phone' => '01012345678', 'password' => 'wrong-'.$i]);
    }

    $response = $this->post(route('site.login.store'), ['phone' => '01012345678', 'password' => 'secret-pass']);

    $response->assertSessionHasErrors('phone');
    expect(session('errors')->first('phone'))->toContain(explode(':', __('storefront::storefront.errors.too_many_logins'))[0]);
    $this->assertGuest('student');
});

it('منطقة الطالب للطالب الداخل بس، والضيف بيروح لدخول الموقع', function (): void {
    $this->get(route('site.dashboard'))->assertRedirect(route('site.login'));
    $this->get(route('site.wallet'))->assertRedirect(route('site.login'));
});

it('الطالب الداخل مالوش دخول على لوحة التحكم', function (): void {
    sfLogin();

    $response = $this->get('/admin');

    expect($response->getStatusCode())->not->toBe(200);
    $this->assertGuest('web');
});

it('الخروج بيقفل جلسة الطالب', function (): void {
    sfLogin();

    $this->post(route('site.logout'))->assertRedirect(route('site.home'));
    $this->assertGuest('student');
});

it('صفحات الطالب كلها بتفتح', function (string $route): void {
    sfLogin();

    $this->get(route($route))->assertOk()->assertSee('data-tafra-credit', escape: false);
})->with(['site.dashboard', 'site.my-courses', 'site.wallet', 'site.invoices', 'site.exams', 'site.profile']);

it('الطالب بيعدّل بياناته وكلمة السر', function (): void {
    $level = EducationalLevel::factory()->create();
    $student = sfLogin(sfStudent(['password' => 'secret-pass']));

    $this->put(route('site.profile.update'), ['name' => 'New Name Here', 'parent_phone' => '01198765432', 'educational_level_id' => $level->getKey()])
        ->assertRedirect(route('site.profile'));

    expect($student->fresh()->name)->toBe('New Name Here')
        ->and($student->fresh()->phone)->toBe($student->phone);

    $this->put(route('site.profile.password'), ['current_password' => 'wrong', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
        ->assertSessionHasErrorsIn('password', 'current_password');

    $this->put(route('site.profile.password'), ['current_password' => 'secret-pass', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
        ->assertRedirect(route('site.profile'));

    expect(Hash::check('brand-new-pass', $student->fresh()->password))->toBeTrue();
});

it('صفحات الدخول والتسجيل ونسيت كلمة السر بتفتح', function (): void {
    sfSiteSettings(['whatsapp_number' => '01012345678']);

    $this->get(route('site.login'))->assertOk();
    $this->get(route('site.register'))->assertOk()->assertSee(__('storefront::storefront.governorates.cairo'));
    $this->get(route('site.password.forgot'))->assertOk()->assertSee('https://wa.me/201012345678', escape: false);
});
