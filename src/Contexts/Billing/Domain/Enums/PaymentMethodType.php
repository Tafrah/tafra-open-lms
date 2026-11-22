<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethodType: string implements HasColor, HasIcon, HasLabel
{
    case VodafoneCash = 'vodafone_cash';
    case EtisalatCash = 'etisalat_cash';
    case OrangeCash = 'orange_cash';
    case Instapay = 'instapay';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';

    public function getLabel(): string
    {
        return __("billing::billing.enums.payment_method_type.{$this->value}");
    }

    /** ألوان Filament الدلالية بس — مفيش hex برا ملف الثيم */
    public function getColor(): string
    {
        return match ($this) {
            self::VodafoneCash => 'danger',
            self::EtisalatCash => 'success',
            self::OrangeCash => 'warning',
            self::Instapay => 'primary',
            self::BankTransfer => 'info',
            self::Other => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::VodafoneCash, self::EtisalatCash, self::OrangeCash => 'heroicon-o-device-phone-mobile',
            self::Instapay => 'heroicon-o-bolt',
            self::BankTransfer => 'heroicon-o-building-library',
            self::Other => 'heroicon-o-banknotes',
        };
    }

    /** محفظة موبايل (ليها حد شهري من شركة الاتصالات) */
    public function isMobileWallet(): bool
    {
        return in_array($this, [self::VodafoneCash, self::EtisalatCash, self::OrangeCash], true);
    }
}
