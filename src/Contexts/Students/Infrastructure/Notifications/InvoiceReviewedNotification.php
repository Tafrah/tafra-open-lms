<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Infrastructure\Notifications;

/** الإيصال اتقبل (الكورس اتفتح) أو اترفض (بالسبب). */
final class InvoiceReviewedNotification extends StudentNotification
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly bool $approved,
        public readonly string $course,
        public readonly ?string $reason,
        public readonly ?string $courseSlug,
    ) {
        parent::__construct();
    }

    public static function key(): string
    {
        return 'student_invoice_reviewed';
    }

    protected function kind(): string
    {
        return $this->approved ? 'invoice_approved' : 'invoice_rejected';
    }

    protected function params(): array
    {
        return ['course' => $this->course, 'reason' => $this->reason, 'invoice_id' => $this->invoiceId];
    }

    protected function link(): array
    {
        return $this->approved && $this->courseSlug !== null
            ? ['site.learn.course', ['slug' => $this->courseSlug]]
            : ['site.invoices', []];
    }
}
