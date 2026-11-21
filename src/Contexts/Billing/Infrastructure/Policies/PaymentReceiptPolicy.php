<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * الإيصالات — بتتبع صلاحيات الفواتير.
 */
final class PaymentReceiptPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'payment_invoices';
    }
}
