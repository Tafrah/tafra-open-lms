<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Presentation\Filament\Actions\InvoiceReviewActions;

final class PaymentInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('billing::billing.invoice.fields.number'))
                    ->searchable()
                    ->copyable()
                    ->weight(FontWeight::Medium),
                TextColumn::make('student.name')
                    ->label(__('billing::billing.invoice.fields.student'))
                    ->description(fn (PaymentInvoice $record): ?string => $record->student?->phone)
                    ->searchable(),
                TextColumn::make('bootcamp.name')
                    ->label(__('billing::billing.invoice.fields.bootcamp'))
                    ->wrap(),
                TextColumn::make('amount')
                    ->label(__('billing::billing.invoice.fields.amount'))
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('payment_gateway')
                    ->label(__('billing::billing.invoice.fields.gateway'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('common.status'))
                    ->badge()
                    ->description(fn (PaymentInvoice $record): ?string => $record->status === InvoiceStatus::Pending && $record->receipts->isEmpty()
                        ? __('billing::billing.invoice.no_receipt_yet')
                        : null),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y — H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('common.status'))
                    ->options(InvoiceStatus::class),
                SelectFilter::make('payment_gateway')
                    ->label(__('billing::billing.invoice.fields.gateway'))
                    ->options(PaymentGateway::class),
                Filter::make('has_receipt')
                    ->label(__('billing::billing.invoice.filters.has_receipt'))
                    ->query(fn ($query) => $query->whereHas('receipts')),
            ])
            ->recordActions([
                ViewAction::make()->label(__('billing::billing.invoice.actions.review')),
                InvoiceReviewActions::approve(),
                InvoiceReviewActions::reject(),
            ])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('billing::billing.invoice.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('billing::billing.invoice.empty.description'));
    }
}
