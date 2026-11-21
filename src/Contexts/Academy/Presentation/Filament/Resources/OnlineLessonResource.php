<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Pages;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Schemas\OnlineLessonForm;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Tables\OnlineLessonsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class OnlineLessonResource extends Resource
{
    protected static ?string $model = OnlineLesson::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-play-circle';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Learning->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('academy::academy.lesson.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('academy::academy.lesson.plural');
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
        return OnlineLessonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnlineLessonsTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOnlineLessons::route('/'),
            'create' => Pages\CreateOnlineLesson::route('/create'),
            'edit' => Pages\EditOnlineLesson::route('/{record}/edit'),
        ];
    }
}
