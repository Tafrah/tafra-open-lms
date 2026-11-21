<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Enums\TransactionType;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;

final class MoneyTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('billing::billing.transaction.fields.student'))
                    ->description(fn (MoneyTransaction $record): ?string => $record->student?->phone)
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('billing::billing.transaction.fields.type'))
                    ->badge(),
                TextColumn::make('amount')
                    ->label(__('billing::billing.transaction.fields.amount'))
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('source')
                    ->label(__('billing::billing.transaction.fields.source'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('balance_after')
                    ->label(__('billing::billing.transaction.fields.balance_after'))
                    ->numeric(2),
                TextColumn::make('creator.name')
                    ->label(__('billing::billing.transaction.fields.creator'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y — H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label(__('billing::billing.transaction.fields.type'))
                    ->options(TransactionType::class),
                SelectFilter::make('source')
                    ->label(__('billing::billing.transaction.fields.source'))
                    ->options(TransactionSource::class),
            ])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('billing::billing.transaction.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('billing::billing.transaction.empty.description'));
    }
}
