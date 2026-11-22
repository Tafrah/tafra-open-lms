<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;

/**
 * فواتير الطالب — تبويب في صفحة الطالب (StudentResource). عايش في Billing
 * لأن الأعمدة والروابط بتاعة الفواتير؛ Students بيسجّله بس في `getRelations()`.
 * الظهور بصلاحية `view_any.payment_invoices` (الـ Policy بتاعة الفاتورة).
 */
final class StudentInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('billing::billing.invoice.plural');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('bootcamp:id,name'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label(__('billing::billing.invoice.fields.number'))
                    ->copyable(),
                TextColumn::make('bootcamp.name')
                    ->label(__('billing::billing.invoice.fields.bootcamp'))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('amount')
                    ->label(__('billing::billing.invoice.fields.amount'))
                    ->numeric(2),
                TextColumn::make('payment_gateway')
                    ->label(__('billing::billing.invoice.fields.gateway'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('common.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y — H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('billing::billing.invoice.actions.review'))
                    ->icon('heroicon-o-eye')
                    ->authorize('view')
                    ->url(fn (PaymentInvoice $record): string => PaymentInvoiceResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(__('billing::billing.invoice.student_empty.heading'))
            ->emptyStateDescription(__('billing::billing.invoice.student_empty.description'));
    }
}
