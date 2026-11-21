<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * كورسات الكوبون.
 */
final class CouponItemPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'coupons';
    }
}
