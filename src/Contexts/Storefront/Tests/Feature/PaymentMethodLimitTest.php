<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Contexts\Billing\Application\Actions\SubmitReceiptAction;
use Src\Contexts\Billing\Application\Queries\PaymentMethodUsage;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Support\Application\Contracts\DiskResolver;

beforeEach(function (): void {
    inTenant();
    Storage::fake(app(DiskResolver::class)->for('receipt'));
    Storage::fake('public');
});

/** تحويل اتقبل على الرقم ده بالمبلغ ده في الوقت ده */
function approvedOn(PaymentMethod $method, float $amount, ?CarbonInterface $at = null): void
{
    $invoice = PaymentInvoice::factory()->create(['amount' => $amount, 'status' => InvoiceStatus::Paid, 'paid_at' => $at ?? now()]);
    PaymentReceipt::factory()->create([
        'payment_invoice_id' => $invoice->getKey(),
        'student_id' => $invoice->student_id,
        'payment_method_id' => $method->getKey(),
        'amount' => $amount,
        'status' => ReceiptStatus::Approved,
    ]);
}

it('الرقم اللي وصل حده الشهري بيختفي من صفحة الدفع ومابيقبلش إيصالات', function (): void {
    $course = sfCourse(['price' => 100]);
    $student = sfLogin();
    $full = PaymentMethod::factory()->create(['name' => 'Full wallet', 'account_number' => '01011111111', 'monthly_limit' => 500]);
    $open = PaymentMethod::factory()->create(['name' => 'Open wallet', 'account_number' => '01022222222', 'monthly_limit' => 5000]);
    approvedOn($full, 500);
    approvedOn($open, 4000);
    // الشهر اللي فات مابيتحسبش
    approvedOn($open, 3000, now()->subMonthNoOverflow());

    $this->post(route('site.checkout.transfer', $course->slug));
    $invoice = PaymentInvoice::query()->where('student_id', $student->getKey())->firstOrFail();

    $this->get(route('site.checkout.show', $invoice->number))
        ->assertOk()
        ->assertSee('01022222222')
        ->assertDontSee('01011111111');

    $this->post(route('site.checkout.receipt', $invoice->number), [
        'payment_method_id' => $full->getKey(),
        'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        'sender_phone' => '01011112222',
    ])->assertSessionHasErrors('payment_method_id');

    expect(fn () => app(SubmitReceiptAction::class)->handle($student, $invoice, $full, UploadedFile::fake()->image('r.jpg')))
        ->toThrow(BillingException::class);
});

it('الحسبة: المقبول الشهر ده بس، ومن غير حد = متاح دايمًا', function (): void {
    $limited = PaymentMethod::factory()->create(['monthly_limit' => 1000]);
    $unlimited = PaymentMethod::factory()->create(['monthly_limit' => null]);
    approvedOn($limited, 400);
    approvedOn($limited, 300);
    approvedOn($unlimited, 99999);

    $usage = app(PaymentMethodUsage::class);
    expect($usage->usedThisMonth($limited))->toBe(700.0)
        ->and($usage->isAvailable($limited))->toBeTrue()
        ->and($usage->isAvailable($unlimited))->toBeTrue();

    approvedOn($limited, 300);
    $usage->forget();

    expect($usage->isAvailable($limited))->toBeFalse()
        ->and($usage->availableForCheckout()->modelKeys())->toBe([$unlimited->getKey()]);
});
