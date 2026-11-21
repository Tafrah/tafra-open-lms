<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Src\Contexts\Billing\Domain\Enums\PaymentMethodType;

final class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('billing::billing.method.fields.name'))
                ->required()
                ->maxLength(120),
            Select::make('type')
                ->label(__('billing::billing.method.fields.type'))
                ->options(PaymentMethodType::class)
                ->native(false)
                ->required(),
            TextInput::make('account_number')
                ->label(__('billing::billing.method.fields.account_number'))
                ->required()
                ->maxLength(64),
            TextInput::make('account_name')
                ->label(__('billing::billing.method.fields.account_name'))
                ->maxLength(255),
            Textarea::make('instructions')
                ->label(__('billing::billing.method.fields.instructions'))
                ->rows(2)
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->label(__('billing::billing.method.fields.is_active'))
                ->default(true),
        ]);
    }
}
