<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Events\InvoiceReviewed;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * المدرس/المساعد بيقبل الإيصال (الكورس بيتفتح) أو بيرفضه بسبب بيوصل للطالب.
 */
final readonly class ReviewInvoiceAction
{
    public function __construct(
        private CheckoutAction $checkout,
        private BootcampAccess $access,
        private Dispatcher $events,
    ) {}

    public function approve(PaymentInvoice $invoice, User $actor): PaymentInvoice
    {
        Gate::forUser($actor)->authorize('approve', $invoice);

        DB::transaction(function () use ($invoice, $actor): void {
            $locked = PaymentInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();
            throw_unless($locked->status === InvoiceStatus::Pending, BillingException::because('invoice_closed'));

            $student = $locked->student;
            $bootcamp = $locked->bootcamp;
            throw_if($student === null || $bootcamp === null, BillingException::because('invoice_broken'));
            // الطالب اشترى الكورس بطريقة تانية في الأثناء — القبول هنا معناه إنه دفع مرتين
            $this->access->forget();
            throw_if($this->access->has($student, $bootcamp), BillingException::because('already_enrolled_review'));

            $locked->receipts()->where('status', ReceiptStatus::Pending)->update(['status' => ReceiptStatus::Approved]);
            $this->checkout->markPaid($locked, $student, $bootcamp, EnrollmentSource::Receipt, (int) $actor->getKey());
        });

        $invoice->refresh();
        $this->events->dispatch(new InvoiceReviewed((int) $invoice->getKey(), approved: true));

        return $invoice;
    }

    public function reject(PaymentInvoice $invoice, User $actor, string $reason): PaymentInvoice
    {
        Gate::forUser($actor)->authorize('reject', $invoice);

        DB::transaction(function () use ($invoice, $actor, $reason): void {
            $locked = PaymentInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();
            throw_unless($locked->status === InvoiceStatus::Pending, BillingException::because('invoice_closed'));

            $locked->receipts()->where('status', ReceiptStatus::Pending)->update(['status' => ReceiptStatus::Rejected]);
            $locked->update([
                'status' => InvoiceStatus::Rejected,
                'reject_reason' => $reason,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ]);
        });

        $invoice->refresh();
        $this->events->dispatch(new InvoiceReviewed((int) $invoice->getKey(), approved: false));

        return $invoice;
    }
}
