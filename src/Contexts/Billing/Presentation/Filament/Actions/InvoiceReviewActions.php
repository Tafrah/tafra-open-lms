<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Auth\Access\AuthorizationException;
use Src\Contexts\Billing\Application\Actions\ReviewInvoiceAction;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Identity\Domain\Models\User;

/** زرارين القبول والرفض — نفس الزرار في الجدول وفي صفحة المراجعة. */
final class InvoiceReviewActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label(__('billing::billing.invoice.actions.approve'))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->authorize('approve')
            ->visible(fn (PaymentInvoice $record): bool => $record->status === InvoiceStatus::Pending)
            ->requiresConfirmation()
            ->modalDescription(__('billing::billing.invoice.confirm.approve'))
            ->action(fn (PaymentInvoice $record) => app(ReviewInvoiceAction::class)->approve($record, self::actor()))
            ->successNotificationTitle(__('billing::billing.invoice.notifications.approved'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label(__('billing::billing.invoice.actions.reject'))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->authorize('reject')
            ->visible(fn (PaymentInvoice $record): bool => $record->status === InvoiceStatus::Pending)
            ->schema([
                Textarea::make('reason')
                    ->label(__('billing::billing.invoice.fields.reject_reason'))
                    ->required()
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->action(fn (PaymentInvoice $record, array $data) => app(ReviewInvoiceAction::class)->reject($record, self::actor(), (string) $data['reason']))
            ->successNotificationTitle(__('billing::billing.invoice.notifications.rejected'));
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthorizationException::class);

        return $user;
    }
}
