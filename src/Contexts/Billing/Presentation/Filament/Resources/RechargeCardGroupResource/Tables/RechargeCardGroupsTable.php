<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;

final class RechargeCardGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('billing::billing.card_group.fields.name'))
                    ->searchable()
                    ->weight(FontWeight::Medium),
                TextColumn::make('type')
                    ->label(__('billing::billing.card_group.fields.type'))
                    ->badge(),
                TextColumn::make('value')
                    ->label(__('billing::billing.card_group.fields.value'))
                    ->state(fn (RechargeCardGroup $record): string => $record->valueLabel())
                    ->wrap(),
                TextColumn::make('used_count')
                    ->label(__('billing::billing.card_group.fields.used'))
                    ->formatStateUsing(fn (RechargeCardGroup $record): string => fc_number((int) $record->getAttribute('used_count')).' / '.fc_number($record->cards_count)),
                IconColumn::make('is_active')
                    ->label(__('billing::billing.card_group.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make()])
            ->headerActions([CreateAction::make()])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('billing::billing.card_group.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('billing::billing.card_group.empty.description'));
    }
}
