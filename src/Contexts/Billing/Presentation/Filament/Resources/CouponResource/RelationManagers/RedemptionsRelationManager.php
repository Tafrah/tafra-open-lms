<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Billing\Domain\Models\CouponRedemption;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;

/** مين استخدم الكوبون، على أنهي فاتورة، وخد خصم قد إيه. */
final class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('billing::billing.coupon.redemptions.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['student:id,name,phone', 'invoice:id,tenant_id,number']))
            ->defaultSort('redeemed_at', 'desc')
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('billing::billing.coupon.redemptions.student'))
                    ->description(fn (CouponRedemption $record): ?string => $record->student?->phone)
                    ->placeholder('—'),
                TextColumn::make('invoice.number')
                    ->label(__('billing::billing.coupon.redemptions.invoice'))
                    ->placeholder('—')
                    ->url(fn (CouponRedemption $record): ?string => $record->invoice !== null
                        && (auth()->user()?->can('view', $record->invoice) ?? false)
                        ? PaymentInvoiceResource::getUrl('view', ['record' => $record->invoice])
                        : null),
                TextColumn::make('original_amount')
                    ->label(__('billing::billing.coupon.redemptions.original'))
                    ->numeric(2),
                TextColumn::make('discount_amount')
                    ->label(__('billing::billing.coupon.redemptions.discount'))
                    ->numeric(2)
                    ->color('success'),
                TextColumn::make('final_amount')
                    ->label(__('billing::billing.coupon.redemptions.final'))
                    ->numeric(2),
                TextColumn::make('redeemed_at')
                    ->label(__('billing::billing.coupon.redemptions.date'))
                    ->dateTime('d M Y — H:i')
                    ->sortable(),
            ])
            ->emptyStateHeading(__('billing::billing.coupon.redemptions.empty_heading'))
            ->emptyStateDescription(__('billing::billing.coupon.redemptions.empty_description'));
    }
}
