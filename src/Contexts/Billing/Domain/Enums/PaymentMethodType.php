<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethodType: string implements HasLabel
{
    case VodafoneCash = 'vodafone_cash';
    case Instapay = 'instapay';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';

    public function getLabel(): string
    {
        return __("billing::billing.enums.payment_method_type.{$this->value}");
    }
}
