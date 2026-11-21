<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return __("billing::billing.enums.invoice_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Rejected => 'danger',
            self::Canceled => 'gray',
        };
    }
}
