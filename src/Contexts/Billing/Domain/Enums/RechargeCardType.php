<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RechargeCardType: string implements HasColor, HasLabel
{
    case Wallet = 'wallet';
    case Bootcamp = 'bootcamp';

    public function getLabel(): string
    {
        return __("billing::billing.enums.recharge_card_type.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Wallet => 'info',
            self::Bootcamp => 'primary',
        };
    }
}
