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
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Billing\Application\Queries\PaymentMethodUsage;
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
                    ->description(fn (PaymentMethod $record): string => $record->type->getLabel())
                    ->icon(fn (PaymentMethod $record): string => $record->type->getIcon())
                    ->iconColor(fn (PaymentMethod $record): string => $record->type->getColor()),
                TextColumn::make('account_number')
                    ->label(__('billing::billing.method.fields.account_number'))
                    ->copyable(),
                // المقبول الشهر ده مقابل الحد — الرقم اللي وصل حده بيختفي من صفحة الدفع
                TextColumn::make('monthly_limit')
                    ->label(__('billing::billing.method.fields.usage'))
                    ->state(fn (PaymentMethod $record): string => self::usage($record))
                    ->badge()
                    ->color(fn (PaymentMethod $record): string => match (true) {
                        $record->monthly_limit === null => 'gray',
                        ! app(PaymentMethodUsage::class)->isAvailable($record) && $record->is_active => 'danger',
                        app(PaymentMethodUsage::class)->usedThisMonth($record) >= 0.8 * (float) $record->monthly_limit => 'warning',
                        default => 'success',
                    })
                    ->tooltip(fn (PaymentMethod $record): ?string => $record->monthly_limit !== null
                        && $record->is_active && ! app(PaymentMethodUsage::class)->isAvailable($record)
                        ? __('billing::billing.method.limit_reached')
                        : null),
                TextColumn::make('account_name')
                    ->label(__('billing::billing.method.fields.account_name'))
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label(__('billing::billing.method.fields.is_active'))
                    ->disabled(fn (PaymentMethod $record): bool => ! (auth()->user()?->can('update', $record) ?? false))
                    ->updateStateUsing(function (PaymentMethod $record, bool $state): bool {
                        Gate::authorize('update', $record);
                        $record->update(['is_active' => $state]);

                        return $state;
                    }),
            ])
            ->defaultSort('order')
            ->reorderable('order', fn (): bool => auth()->user()?->can('create', PaymentMethod::class) ?? false)
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

    private static function usage(PaymentMethod $record): string
    {
        $used = fc_number(app(PaymentMethodUsage::class)->usedThisMonth($record));

        return $record->monthly_limit === null
            ? __('billing::billing.method.usage_unlimited', ['used' => $used])
            : __('billing::billing.method.usage_of', ['used' => $used, 'limit' => fc_number((float) $record->monthly_limit)]);
    }
}
