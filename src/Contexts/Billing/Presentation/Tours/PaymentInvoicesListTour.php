<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Pages\ListPaymentInvoices;

final class PaymentInvoicesListTour extends Tour
{
    private const PREFIX = 'billing::billing.tours.invoice';

    public static function key(): string
    {
        return 'billing.invoice';
    }

    public static function page(): string
    {
        return ListPaymentInvoices::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
        ];
    }
}
