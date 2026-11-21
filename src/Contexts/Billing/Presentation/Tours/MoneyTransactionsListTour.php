<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource\Pages\ListMoneyTransactions;

final class MoneyTransactionsListTour extends Tour
{
    private const PREFIX = 'billing::billing.tours.transaction';

    public static function key(): string
    {
        return 'billing.transaction';
    }

    public static function page(): string
    {
        return ListMoneyTransactions::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
        ];
    }
}
