<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource\Pages;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource\Schemas\EducationalLevelForm;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource\Tables\EducationalLevelsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class EducationalLevelResource extends Resource
{
    protected static ?string $model = EducationalLevel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Learning->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('academy::academy.level.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('academy::academy.level.plural');
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
        return EducationalLevelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EducationalLevelsTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEducationalLevels::route('/'),
        ];
    }
}
