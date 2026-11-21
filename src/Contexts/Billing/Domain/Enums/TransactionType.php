<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionType: string implements HasColor, HasLabel
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';

    public function getLabel(): string
    {
        return __("billing::billing.enums.transaction_type.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Deposit => 'success',
            self::Withdraw => 'danger',
        };
    }
}
