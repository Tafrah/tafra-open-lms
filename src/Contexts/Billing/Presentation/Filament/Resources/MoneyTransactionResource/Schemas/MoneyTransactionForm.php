<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class MoneyTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount')
                ->label(__('billing::billing.transaction.fields.amount'))
                ->disabled(),
        ]);
    }
}
