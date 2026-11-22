<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Infrastructure\Listeners;

use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Domain\Events\InvoiceReviewed;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Students\Infrastructure\Notifications\InvoiceReviewedNotification;

/**
 * Billing → Students عبر الحدث: الطالب بيلاقي في الجرس إن إيصاله اتقبل أو اترفض.
 * متزامن عن قصد — الحدث بيتبعت جوه سياق المؤسسة، والطابور مالوش سياق.
 */
final class NotifyStudentOfInvoiceReview
{
    public function handle(InvoiceReviewed $event): void
    {
        $invoice = PaymentInvoice::query()
            ->with(['student', 'bootcamp:id,name,slug'])
            ->find($event->invoiceId);

        $student = $invoice?->student;

        if ($invoice === null || $student === null) {
            return;
        }

        // الكورس ممكن يكون اتحذف بعد الفاتورة — الإشعار بيوصل برضه
        $course = $invoice->bootcamp instanceof Bootcamp ? $invoice->bootcamp : null;

        $student->notifyNow(new InvoiceReviewedNotification(
            invoiceId: (int) $invoice->getKey(),
            approved: $event->approved,
            course: (string) ($course === null ? '' : $course->name),
            reason: $event->approved ? null : $invoice->reject_reason,
            courseSlug: $course?->slug,
        ));
    }
}
