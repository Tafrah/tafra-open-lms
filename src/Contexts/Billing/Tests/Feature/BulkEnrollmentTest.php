<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\BulkEnrollmentAction;
use Src\Contexts\Billing\Application\Actions\EnrollStudentAction;
use Src\Contexts\Billing\Application\Actions\WalletAction;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

beforeEach(function (): void {
    inTenant();
});

it('بيفهم الأرقام بأي شكل مصري ويشيل التكرار', function (): void {
    expect(BulkEnrollmentAction::parse("01012345678\n+20 101 234 5678\n٠١٠١٢٣٤٥٦٧٩, 00201012345680\n\n  "))
        ->toBe(['01012345678', '01012345679', '01012345680']);
});

it('التفعيل المجاني بيفتح الكورس ويفرز المشترك أصلًا واللي مالوش حساب', function (): void {
    $admin = userWithRole('admin');
    $course = Bootcamp::factory()->create(['price' => 200]);
    $a = Student::factory()->create(['phone' => '01011111111']);
    $b = Student::factory()->create(['phone' => '01022222222']);
    app(EnrollStudentAction::class)->handle($b, $course, EnrollmentSource::Admin);

    $result = app(BulkEnrollmentAction::class)->activate($course, "+201011111111\n01022222222\n01099999999", false, $admin);

    expect($result->done)->toBe(['01011111111'])
        ->and($result->skipped)->toBe(['01022222222'])
        ->and($result->notFound)->toBe(['01099999999'])
        ->and(app(BootcampAccess::class)->has($a, $course))->toBeTrue()
        ->and(Enrollment::query()->where('student_id', $a->getKey())->first()->enrolled_by)->toBe($admin->getKey())
        // مجاني = مفيش فلوس اتحركت
        ->and(MoneyTransaction::query()->count())->toBe(0);
});

it('التفعيل بالمحفظة بيخصم عن طريق WalletAction، واللي رصيده ناقص بيتعدّ لوحده', function (): void {
    $admin = userWithRole('admin');
    $course = Bootcamp::factory()->create(['price' => 150]);
    $rich = Student::factory()->create(['phone' => '01033333333']);
    $poor = Student::factory()->create(['phone' => '01044444444']);
    app(WalletAction::class)->deposit($rich, 200, TransactionSource::Admin);
    app(WalletAction::class)->deposit($poor, 100, TransactionSource::Admin);

    $result = app(BulkEnrollmentAction::class)->activate($course, "01033333333\n01044444444", true, $admin);

    expect($result->done)->toBe(['01033333333'])
        ->and($result->insufficientBalance)->toBe(['01044444444'])
        ->and((float) $rich->fresh()->balance)->toBe(50.0)
        ->and((float) $poor->fresh()->balance)->toBe(100.0)
        ->and(app(BootcampAccess::class)->has($poor, $course))->toBeFalse();

    $invoice = PaymentInvoice::query()->where('student_id', $rich->getKey())->firstOrFail();
    expect($invoice->payment_gateway)->toBe(PaymentGateway::Wallet)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and(MoneyTransaction::query()->where('student_id', $rich->getKey())->where('source', TransactionSource::Purchase)->count())->toBe(1);
});

it('الإلغاء بالأرقام بيقفل الكورس ويفرز اللي مش مشترك', function (): void {
    $admin = userWithRole('admin');
    $course = Bootcamp::factory()->create();
    $in = Student::factory()->create(['phone' => '01055555555']);
    Student::factory()->create(['phone' => '01066666666']);
    app(EnrollStudentAction::class)->handle($in, $course, EnrollmentSource::Admin);

    $result = app(BulkEnrollmentAction::class)->cancel($course, "01055555555\n01066666666\n01077777777", $admin);

    expect($result->done)->toBe(['01055555555'])
        ->and($result->skipped)->toBe(['01066666666'])
        ->and($result->notFound)->toBe(['01077777777'])
        ->and(app(BootcampAccess::class)->has($in, $course))->toBeFalse();
});

it('الأرقام من مؤسسة تانية مابتتشافش', function (): void {
    $admin = userWithRole('admin');
    $course = Bootcamp::factory()->create();
    $other = Tenant::factory()->create();
    app(TenantContext::class)
        ->forTenant($other->getKey(), fn () => Student::factory()->create(['phone' => '01088888888']));

    $result = app(BulkEnrollmentAction::class)->activate($course, '01088888888', false, $admin);

    expect($result->notFound)->toBe(['01088888888'])->and(Enrollment::query()->count())->toBe(0);
});

it('المساعد يقدر (create.enrollments) والمشاهد لأ', function (): void {
    $course = Bootcamp::factory()->create();
    Student::factory()->create(['phone' => '01012121212']);

    $result = app(BulkEnrollmentAction::class)->activate($course, '01012121212', false, userWithRole('assistant'));
    expect($result->done)->toBe(['01012121212']);

    expect(fn () => app(BulkEnrollmentAction::class)->activate($course, '01012121212', false, userWithRole('viewer')))
        ->toThrow(AuthorizationException::class);
    expect(fn () => app(BulkEnrollmentAction::class)->cancel($course, '01012121212', userWithRole('viewer')))
        ->toThrow(AuthorizationException::class);
});

it('فيه حد أقصى للأرقام في المرة', function (): void {
    $course = Bootcamp::factory()->create();
    $phones = implode("\n", array_map(fn (int $i): string => '010'.str_pad((string) $i, 8, '0', STR_PAD_LEFT), range(1, BulkEnrollmentAction::MAX_PHONES + 1)));

    expect(fn () => app(BulkEnrollmentAction::class)->activate($course, $phones, false, userWithRole('admin')))
        ->toThrow(BillingException::class);
});
