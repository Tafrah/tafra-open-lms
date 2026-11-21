<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * الفواتير: القبول والرفض للفاتورة المعلّقة بس.
 */
final class PaymentInvoicePolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'payment_invoices';
    }

    public function approve(User $user, PaymentInvoice $record): Response
    {
        return $this->owned($user, $record, 'approve')
            ->rule($record->status === InvoiceStatus::Pending, 'invoice.not_pending')
            ->response();
    }

    public function reject(User $user, PaymentInvoice $record): Response
    {
        return $this->owned($user, $record, 'reject')
            ->rule($record->status === InvoiceStatus::Pending, 'invoice.not_pending')
            ->response();
    }
}
