<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource\Pages;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource\Schemas\MoneyTransactionForm;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource\Tables\MoneyTransactionsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class MoneyTransactionResource extends Resource
{
    protected static ?string $model = MoneyTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'uuid';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Finance->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('billing::billing.transaction.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('billing::billing.transaction.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['student:id,name,phone', 'creator:id,name']);
    }

    public static function form(Schema $schema): Schema
    {
        return MoneyTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MoneyTransactionsTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMoneyTransactions::route('/'),
        ];
    }
}
