<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentGateway: string implements HasColor, HasLabel
{
    case ManualTransfer = 'manual_transfer';
    case Wallet = 'wallet';
    case RechargeCard = 'recharge_card';
    case Free = 'free';

    public function getLabel(): string
    {
        return __("billing::billing.enums.payment_gateway.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ManualTransfer => 'primary',
            self::Wallet => 'info',
            self::RechargeCard => 'warning',
            self::Free => 'gray',
        };
    }
}
