<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Pages;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\RelationManagers;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Schemas\BootcampSectionForm;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Tables\BootcampSectionsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class BootcampSectionResource extends Resource
{
    protected static ?string $model = BootcampSection::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Learning->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('academy::academy.section.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('academy::academy.section.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('bootcamp:id,name');
    }

    public static function form(Schema $schema): Schema
    {
        return BootcampSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BootcampSectionsTable::configure($table);
    }

    /** @return array<int, class-string> */
    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBootcampSections::route('/'),
            'edit' => Pages\EditBootcampSection::route('/{record}/edit'),
        ];
    }
}
