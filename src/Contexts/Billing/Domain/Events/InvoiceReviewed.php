<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Events;

/** الإيصال اتقبل أو اترفض — الطالب بيتبلّغ على الموقع. */
final readonly class InvoiceReviewed
{
    public function __construct(
        public int $invoiceId,
        public bool $approved,
    ) {}
}
