<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\CheckoutAction;
use Src\Contexts\Billing\Application\Actions\ReviewInvoiceAction;
use Src\Contexts\Billing\Application\Actions\SubmitReceiptAction;
use Src\Contexts\Billing\Application\Actions\WalletAction;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\CouponScope;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Students\Domain\Models\Student;

beforeEach(function (): void {
    inTenant();
    Storage::fake('local');
    Storage::fake('public');
});

it('الشراء بالمحفظة بيخصم الرصيد ويفتح الكورس فورًا', function (): void {
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->create(['price' => 150]);
    app(WalletAction::class)->deposit($student, 200, TransactionSource::Admin);

    $invoice = app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::Wallet);

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and((float) $student->fresh()->balance)->toBe(50.0)
        ->and(app(BootcampAccess::class)->has($student, $bootcamp))->toBeTrue();
});

it('الرصيد الناقص بيرفض الشراء ومابيفتحش حاجة', function (): void {
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->create(['price' => 150]);

    expect(fn () => app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::Wallet))
        ->toThrow(BillingException::class);

    expect(Enrollment::query()->count())->toBe(0)
        ->and((float) $student->fresh()->balance)->toBe(0.0);
});

it('الدفع بالإيصال: فاتورة معلّقة، رفع الصورة، القبول بيفتح الكورس', function (): void {
    $admin = userWithRole('admin');
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->create(['price' => 200]);
    $method = PaymentMethod::factory()->create();

    $invoice = app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::ManualTransfer);
    expect($invoice->status)->toBe(InvoiceStatus::Pending)
        ->and(app(BootcampAccess::class)->has($student, $bootcamp))->toBeFalse();

    $receipt = app(SubmitReceiptAction::class)->handle(
        $student, $invoice, $method, UploadedFile::fake()->image('receipt.jpg'), '01000000000',
    );
    expect($receipt->getFirstMedia('receipt'))->not->toBeNull();

    app(ReviewInvoiceAction::class)->approve($invoice, $admin);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and(Enrollment::query()->latest('id')->first()->source)->toBe(EnrollmentSource::Receipt)
        ->and(app(BootcampAccess::class)->has($student, $bootcamp))->toBeTrue();
});

it('رفض الإيصال بيقفل الفاتورة بالسبب ومابيفتحش الكورس', function (): void {
    $admin = userWithRole('admin');
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->create();

    $invoice = app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::ManualTransfer);
    app(ReviewInvoiceAction::class)->reject($invoice, $admin, 'الصورة مش واضحة');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Rejected)
        ->and($invoice->fresh()->reject_reason)->toBe('الصورة مش واضحة')
        ->and(app(BootcampAccess::class)->has($student, $bootcamp))->toBeFalse();

    // ومينفعش تتقبل بعد الرفض
    expect(fn () => app(ReviewInvoiceAction::class)->approve($invoice->fresh(), $admin))->toThrow(Exception::class);
});

it('فاتورة تحويل معلّقة واحدة بس لكل كورس', function (): void {
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->create();

    $a = app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::ManualTransfer);
    $b = app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::ManualTransfer);

    expect($b->getKey())->toBe($a->getKey());
});

it('خصم المحاضرة والكوبون بيتحسبوا صح، والكوبون بيتسجل مرة واحدة', function (): void {
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->create([
        'price' => 200, 'discount_price' => 150,
        'discount_start_date' => now()->subDay(), 'discount_end_date' => now()->addDay(),
    ]);
    $coupon = Coupon::factory()->create(['code' => 'ELMOGY20', 'value' => 20, 'max_per_user' => 1]);
    app(WalletAction::class)->deposit($student, 500, TransactionSource::Admin);

    $quote = app(CheckoutAction::class)->quote($student, $bootcamp, 'elmogy20');
    expect($quote['subtotal'])->toBe(150.0)->and($quote['discount'])->toBe(30.0)->and($quote['total'])->toBe(120.0);

    app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::Wallet, 'ELMOGY20');

    expect((float) $student->fresh()->balance)->toBe(380.0)
        ->and($coupon->fresh()->current_redemptions)->toBe(1);

    $other = Bootcamp::factory()->create(['price' => 100]);
    expect(fn () => app(CheckoutAction::class)->quote($student, $other, 'ELMOGY20'))->toThrow(BillingException::class);
});

it('كوبون لكورسات محددة مابيشتغلش على غيرها', function (): void {
    $student = Student::factory()->create();
    [$a, $b] = Bootcamp::factory()->count(2)->create(['price' => 100]);
    $coupon = Coupon::factory()->create(['code' => 'ONLYA', 'applicable_type' => CouponScope::Specific]);
    $coupon->items()->create(['item_type' => 'bootcamp', 'item_id' => $a->getKey()]);

    expect(app(CheckoutAction::class)->quote($student, $a, 'ONLYA')['discount'])->toBe(20.0);
    expect(fn () => app(CheckoutAction::class)->quote($student, $b, 'ONLYA'))->toThrow(BillingException::class);
});

it('الكورس المجاني (أو بعد خصم ١٠٠٪) بيفتح من غير دفع', function (): void {
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->free()->create();

    $invoice = app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::ManualTransfer);

    expect($invoice->payment_gateway)->toBe(PaymentGateway::Free)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and(app(BootcampAccess::class)->has($student, $bootcamp))->toBeTrue();
});

it('مدة المشاهدة بتنتهي، وبعدها الكورس بيتقفل', function (): void {
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->free()->create(['can_watch_in_days' => 30]);

    app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::Free);
    expect(app(BootcampAccess::class)->has($student, $bootcamp))->toBeTrue();

    $this->travel(31)->days();
    app(BootcampAccess::class)->forget();

    expect(app(BootcampAccess::class)->has($student, $bootcamp))->toBeFalse();
});

it('الطالب المشترك مايقدرش يشتري نفس الكورس تاني', function (): void {
    $student = Student::factory()->create();
    $bootcamp = Bootcamp::factory()->free()->create();
    app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::Free);

    expect(fn () => app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::Wallet))
        ->toThrow(BillingException::class);
});
