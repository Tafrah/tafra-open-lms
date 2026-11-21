<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum TransactionSource: string implements HasLabel
{
    case RechargeCard = 'recharge_card';
    case Purchase = 'purchase';
    case Admin = 'admin';
    case Refund = 'refund';

    public function getLabel(): string
    {
        return __("billing::billing.enums.transaction_source.{$this->value}");
    }
}
