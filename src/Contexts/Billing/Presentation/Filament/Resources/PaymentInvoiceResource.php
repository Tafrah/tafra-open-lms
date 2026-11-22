<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Src\Contexts\Billing\Application\Queries\ReceiptDuplicates;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Pages;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Schemas\PaymentInvoiceForm;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Tables\PaymentInvoicesTable;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class PaymentInvoiceResource extends Resource
{
    protected static ?string $model = PaymentInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Finance->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('billing::billing.invoice.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('billing::billing.invoice.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! self::canViewAny()) {
            return null;
        }

        $count = Cache::remember(
            'nav:invoice:'.(app(TenantContext::class)->id() ?? 'global'),
            now()->addMinute(),
            fn (): int => PaymentInvoice::query()->where('status', InvoiceStatus::Pending)->whereHas('receipts')->count(),
        );

        return $count > 0 ? fc_number($count) : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return __('billing::billing.invoice.badge');
    }

    public static function getEloquentQuery(): Builder
    {
        // `fraud_flagged`: الإيصال متكرر على فاتورة تانية (ReceiptDuplicates) — subquery واحد للصفحة
        return ReceiptDuplicates::flag(
            parent::getEloquentQuery()->with(['student:id,name,phone', 'bootcamp:id,name', 'receipts.media', 'receipts.method:id,name']),
        );
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentInvoicesTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['number'];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentInvoices::route('/'),
            'view' => Pages\ViewPaymentInvoice::route('/{record}'),
        ];
    }
}
