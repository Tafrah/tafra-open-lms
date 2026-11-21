<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * أرقام التحويل اللي بتظهر للطالب.
 */
final class PaymentMethodPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'payment_methods';
    }
}
