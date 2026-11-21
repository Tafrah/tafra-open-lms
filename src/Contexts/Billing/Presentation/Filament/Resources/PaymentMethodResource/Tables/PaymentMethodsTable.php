<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;

final class PaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('billing::billing.method.fields.name'))
                    ->weight(FontWeight::Medium)
                    ->description(fn (PaymentMethod $record): string => $record->type->getLabel()),
                TextColumn::make('account_number')
                    ->label(__('billing::billing.method.fields.account_number'))
                    ->copyable(),
                TextColumn::make('account_name')
                    ->label(__('billing::billing.method.fields.account_name'))
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label(__('billing::billing.method.fields.is_active')),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->authorizationTooltip(),
                    DeleteAction::make()->authorizationTooltip(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('billing::billing.method.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('billing::billing.method.empty.description'));
    }
}
