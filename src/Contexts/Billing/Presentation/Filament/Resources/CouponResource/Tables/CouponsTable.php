<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Billing\Domain\Enums\CouponType;
use Src\Contexts\Billing\Domain\Models\Coupon;

final class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('billing::billing.coupon.fields.code'))
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Coupon $record): ?string => $record->name),
                TextColumn::make('value')
                    ->label(__('billing::billing.coupon.fields.value'))
                    ->formatStateUsing(fn (Coupon $record): string => $record->type === CouponType::Percentage
                        ? fc_number((float) $record->value).'%'
                        : fc_number((float) $record->value).' '.__('academy::academy.currency')),
                TextColumn::make('current_redemptions')
                    ->label(__('billing::billing.coupon.fields.redemptions'))
                    ->formatStateUsing(fn (Coupon $record): string => fc_number($record->current_redemptions)
                        .($record->max_redemptions !== null ? ' / '.fc_number($record->max_redemptions) : '')),
                TextColumn::make('applicable_type')
                    ->label(__('billing::billing.coupon.fields.applicable_type'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('expires_at')
                    ->label(__('billing::billing.coupon.fields.expires_at'))
                    ->dateTime('d M Y')
                    ->placeholder('∞'),
                ToggleColumn::make('is_active')
                    ->label(__('billing::billing.coupon.fields.is_active'))
                    ->disabled(fn (Coupon $record): bool => ! (auth()->user()?->can('update', $record) ?? false))
                    ->updateStateUsing(function (Coupon $record, bool $state): bool {
                        Gate::authorize('update', $record);
                        $record->update(['is_active' => $state]);

                        return $state;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([TrashedFilter::make()])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()->authorizationTooltip(),
                    DeleteAction::make()->authorizationTooltip(),
                    RestoreAction::make(),
                ])
                    ->label(__('common.actions'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ])
            ->headerActions([CreateAction::make()])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('billing::billing.coupon.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('billing::billing.coupon.empty.description'));
    }
}
