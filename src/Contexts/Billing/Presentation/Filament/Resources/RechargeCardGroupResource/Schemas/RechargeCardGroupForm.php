<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Src\Contexts\Billing\Application\Actions\GenerateRechargeCardsAction;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;

final class RechargeCardGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('billing::billing.card_group.fields.name'))
                        ->helperText(__('billing::billing.card_group.help.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('type')
                        ->label(__('billing::billing.card_group.fields.type'))
                        ->options(RechargeCardType::class)
                        ->default(RechargeCardType::Wallet)
                        ->native(false)
                        ->live()
                        ->required(),
                    TextInput::make('count')
                        ->label(__('billing::billing.card_group.fields.count'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(GenerateRechargeCardsAction::MAX_PER_BATCH)
                        ->default(100)
                        ->required()
                        ->visibleOn('create'),
                    TextInput::make('amount')
                        ->label(__('billing::billing.card_group.fields.amount'))
                        ->numeric()
                        ->minValue(1)
                        ->suffix(__('academy::academy.currency'))
                        ->required(fn (Get $get): bool => self::type($get) === RechargeCardType::Wallet)
                        ->visible(fn (Get $get): bool => self::type($get) === RechargeCardType::Wallet),
                    Select::make('bootcamp_id')
                        ->label(__('billing::billing.card_group.fields.bootcamp'))
                        ->relationship('bootcamp', 'name')
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => self::type($get) === RechargeCardType::Bootcamp)
                        ->visible(fn (Get $get): bool => self::type($get) === RechargeCardType::Bootcamp),
                    DateTimePicker::make('expiry_date')
                        ->label(__('billing::billing.card_group.fields.expiry_date'))
                        ->seconds(false)
                        ->after('now'),
                ]),
        ]);
    }

    private static function type(Get $get): ?RechargeCardType
    {
        $type = $get('type');

        return $type instanceof RechargeCardType ? $type : RechargeCardType::tryFrom((string) $type);
    }
}
