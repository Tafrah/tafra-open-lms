<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Pages;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\RelationManagers;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Schemas\RechargeCardGroupForm;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Tables\RechargeCardGroupsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class RechargeCardGroupResource extends Resource
{
    protected static ?string $model = RechargeCardGroup::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'recharge-cards';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Finance->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('billing::billing.card_group.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('billing::billing.card_group.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('bootcamp:id,name')->withCount(['cards as used_count' => fn (Builder $q) => $q->whereNotNull('used_at')]);
    }

    public static function form(Schema $schema): Schema
    {
        return RechargeCardGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RechargeCardGroupsTable::configure($table);
    }

    /** @return array<int, class-string> */
    public static function getRelations(): array
    {
        return [
            RelationManagers\CardsRelationManager::class,
        ];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRechargeCardGroups::route('/'),
            'create' => Pages\CreateRechargeCardGroup::route('/create'),
            'view' => Pages\ViewRechargeCardGroup::route('/{record}'),
        ];
    }
}
