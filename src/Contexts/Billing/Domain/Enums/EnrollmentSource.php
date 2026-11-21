<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnrollmentSource: string implements HasColor, HasLabel
{
    case Receipt = 'receipt';
    case Wallet = 'wallet';
    case RechargeCard = 'recharge_card';
    case Admin = 'admin';
    case Free = 'free';

    public function getLabel(): string
    {
        return __("billing::billing.enums.enrollment_source.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Receipt => 'primary',
            self::Wallet => 'info',
            self::RechargeCard => 'warning',
            self::Admin => 'gray',
            self::Free => 'success',
        };
    }
}
