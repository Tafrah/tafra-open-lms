<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Pages;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Schemas\CouponForm;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Tables\CouponsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Finance->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('billing::billing.coupon.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('billing::billing.coupon.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['code'];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
