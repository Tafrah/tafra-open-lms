<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource;

final class ListMoneyTransactions extends ListRecords
{
    protected static string $resource = MoneyTransactionResource::class;
}
