<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum CouponScope: string implements HasLabel
{
    case All = 'all';
    case Specific = 'specific';

    public function getLabel(): string
    {
        return __("billing::billing.enums.coupon_scope.{$this->value}");
    }
}
