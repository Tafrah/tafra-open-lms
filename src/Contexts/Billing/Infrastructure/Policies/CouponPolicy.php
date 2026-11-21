<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * الكوبونات.
 */
final class CouponPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'coupons';
    }
}
