<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Pages;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Schemas\PaymentMethodForm;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Tables\PaymentMethodsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Finance->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('billing::billing.method.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('billing::billing.method.plural');
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
        return PaymentMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentMethodsTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentMethods::route('/'),
        ];
    }
}
