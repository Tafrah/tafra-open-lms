<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\Pages;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\RelationManagers;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\Schemas\BootcampForm;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\Tables\BootcampsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class BootcampResource extends Resource
{
    protected static ?string $model = Bootcamp::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Learning->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('academy::academy.bootcamp.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('academy::academy.bootcamp.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['level:id,name,color'])->withCount('sections');
    }

    public static function form(Schema $schema): Schema
    {
        return BootcampForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BootcampsTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    /** @return array<int, class-string> */
    public static function getRelations(): array
    {
        return [
            RelationManagers\SectionsRelationManager::class,
        ];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBootcamps::route('/'),
            'create' => Pages\CreateBootcamp::route('/create'),
            'edit' => Pages\EditBootcamp::route('/{record}/edit'),
        ];
    }
}
