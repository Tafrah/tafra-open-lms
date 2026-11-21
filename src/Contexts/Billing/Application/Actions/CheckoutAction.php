<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Application\Queries\CouponValidator;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\CouponRedemption;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * الطالب بيشتري كورس: بالمحفظة (فوري)، أو بالتحويل اليدوي (فاتورة معلّقة
 * لحد ما الإيصال يتقبل)، أو مجانًا لو السعر بعد الخصم صفر.
 */
final readonly class CheckoutAction
{
    public function __construct(
        private BootcampAccess $access,
        private CouponValidator $coupons,
        private WalletAction $wallet,
        private EnrollStudentAction $enroll,
    ) {}

    /** @return array{subtotal: float, discount: float, total: float, coupon: Coupon|null} */
    public function quote(Student $student, Bootcamp $bootcamp, ?string $couponCode = null): array
    {
        $subtotal = $bootcamp->currentPrice();
        $coupon = filled($couponCode) ? $this->coupons->validate((string) $couponCode, $student, $bootcamp) : null;
        $discount = $coupon?->discountFor($subtotal) ?? 0.0;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => round(max(0.0, $subtotal - $discount), 2),
            'coupon' => $coupon,
        ];
    }

    public function handle(Student $student, Bootcamp $bootcamp, PaymentGateway $gateway, ?string $couponCode = null): PaymentInvoice
    {
        throw_unless($bootcamp->isPublished(), BillingException::because('course_unavailable'));
        throw_if($student->isBlocked(), BillingException::because('student_blocked'));
        throw_if($this->access->has($student, $bootcamp), BillingException::because('already_enrolled'));

        $quote = $this->quote($student, $bootcamp, $couponCode);

        if ($quote['total'] <= 0.0) {
            $gateway = PaymentGateway::Free;
        }

        throw_if(
            $gateway === PaymentGateway::RechargeCard,
            BillingException::because('use_redeem_for_cards'),
        );

        return DB::transaction(function () use ($student, $bootcamp, $gateway, $quote): PaymentInvoice {
            if ($gateway === PaymentGateway::ManualTransfer) {
                // فاتورة معلّقة واحدة لكل كورس — لو موجودة نرجّعها (بنفس السعر الجديد)
                $pending = PaymentInvoice::query()
                    ->where('student_id', $student->getKey())
                    ->where('bootcamp_id', $bootcamp->getKey())
                    ->where('status', InvoiceStatus::Pending)
                    ->lockForUpdate()
                    ->first();

                if ($pending !== null) {
                    $pending->update($this->amounts($quote));

                    return $pending;
                }
            }

            $invoice = PaymentInvoice::query()->create([
                'number' => $this->number(),
                'student_id' => $student->getKey(),
                'bootcamp_id' => $bootcamp->getKey(),
                ...$this->amounts($quote),
                'currency' => $bootcamp->price_currency ?: 'EGP',
                'payment_gateway' => $gateway,
                'status' => InvoiceStatus::Pending,
            ]);

            if ($gateway === PaymentGateway::ManualTransfer) {
                return $invoice;
            }

            if ($gateway === PaymentGateway::Wallet) {
                $this->wallet->withdraw($student, (float) $invoice->amount, TransactionSource::Purchase, $invoice);
            }

            $this->markPaid($invoice, $student, $bootcamp, $gateway === PaymentGateway::Wallet ? EnrollmentSource::Wallet : EnrollmentSource::Free);

            return $invoice;
        });
    }

    /**
     * الفاتورة اتدفعت (محفظة/مجاني/إيصال اتقبل): تسجيل الكوبون + فتح الكورس.
     * بيتنادى من جوه transaction.
     */
    public function markPaid(PaymentInvoice $invoice, Student $student, Bootcamp $bootcamp, EnrollmentSource $source, ?int $reviewerId = null): void
    {
        $invoice->update([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
            'reviewed_by' => $reviewerId,
            'reviewed_at' => $reviewerId !== null ? now() : null,
        ]);

        if ($invoice->coupon_id !== null) {
            $coupon = Coupon::query()->lockForUpdate()->find($invoice->coupon_id);

            if ($coupon !== null) {
                CouponRedemption::query()->create([
                    'coupon_id' => $coupon->getKey(),
                    'student_id' => $student->getKey(),
                    'payment_invoice_id' => $invoice->getKey(),
                    'discount_amount' => $invoice->discount_amount,
                    'original_amount' => $invoice->subtotal,
                    'final_amount' => $invoice->amount,
                    'redeemed_at' => now(),
                ]);
                $coupon->increment('current_redemptions');
            }
        }

        $this->enroll->handle($student, $bootcamp, $source, invoice: $invoice, enrolledBy: $reviewerId);
    }

    /**
     * @param array{subtotal: float, discount: float, total: float, coupon: Coupon|null} $quote
     * @return array<string, mixed>
     */
    private function amounts(array $quote): array
    {
        return [
            'subtotal' => $quote['subtotal'],
            'discount_amount' => $quote['discount'],
            'amount' => $quote['total'],
            'coupon_id' => $quote['coupon']?->getKey(),
        ];
    }

    private function number(): string
    {
        do {
            $number = 'INV-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (PaymentInvoice::query()->withTrashed()->where('number', $number)->exists());

        return $number;
    }
}
