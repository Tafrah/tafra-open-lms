<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PaymentInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('number')
                ->label(__('billing::billing.invoice.fields.number'))
                ->disabled(),
        ]);
    }
}
