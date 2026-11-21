<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Contexts\Billing\Presentation\Filament\Actions\InvoiceReviewActions;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;
use Src\Support\Infrastructure\Filesystem\MediaUrlResolver;

final class ViewPaymentInvoice extends ViewRecord
{
    protected static string $resource = PaymentInvoiceResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    TextEntry::make('number')->label(__('billing::billing.invoice.fields.number'))->copyable(),
                    TextEntry::make('status')->label(__('common.status'))->badge(),
                    TextEntry::make('payment_gateway')->label(__('billing::billing.invoice.fields.gateway'))->badge(),
                    TextEntry::make('student.name')->label(__('billing::billing.invoice.fields.student'))
                        ->helperText(fn (PaymentInvoice $record): ?string => $record->student?->phone),
                    TextEntry::make('bootcamp.name')->label(__('billing::billing.invoice.fields.bootcamp')),
                    TextEntry::make('amount')->label(__('billing::billing.invoice.fields.amount'))
                        ->formatStateUsing(fn (PaymentInvoice $record): string => fc_number((float) $record->amount).' '.__('academy::academy.currency'))
                        ->helperText(fn (PaymentInvoice $record): ?string => (float) $record->discount_amount > 0
                            ? __('billing::billing.invoice.discount_note', ['discount' => fc_number((float) $record->discount_amount)])
                            : null),
                    TextEntry::make('reject_reason')->label(__('billing::billing.invoice.fields.reject_reason'))
                        ->visible(fn (PaymentInvoice $record): bool => filled($record->reject_reason))
                        ->columnSpanFull(),
                ]),
            Section::make(__('billing::billing.invoice.sections.receipts'))
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('receipts')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            ImageEntry::make('image')
                                ->label(__('billing::billing.receipt.fields.image'))
                                ->state(fn (PaymentReceipt $record): ?string => ($media = $record->getFirstMedia('receipt')) !== null
                                    ? app(MediaUrlResolver::class)->url($media)
                                    : null)
                                ->imageHeight(220)
                                ->url(fn (PaymentReceipt $record): ?string => ($media = $record->getFirstMedia('receipt')) !== null
                                    ? app(MediaUrlResolver::class)->url($media)
                                    : null, shouldOpenInNewTab: true)
                                ->columnSpan(2),
                            TextEntry::make('method.name')->label(__('billing::billing.receipt.fields.method')),
                            TextEntry::make('sender_phone')->label(__('billing::billing.receipt.fields.sender_phone'))->placeholder('—')->copyable(),
                            TextEntry::make('transfer_reference')->label(__('billing::billing.receipt.fields.reference'))->placeholder('—'),
                            TextEntry::make('status')->label(__('common.status'))->badge(),
                            TextEntry::make('created_at')->label(__('common.created_at'))->dateTime('d M Y — H:i'),
                        ]),
                ]),
        ]);
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            InvoiceReviewActions::approve(),
            InvoiceReviewActions::reject(),
        ];
    }
}
