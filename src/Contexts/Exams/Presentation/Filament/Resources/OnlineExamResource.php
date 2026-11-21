<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Pages;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\RelationManagers;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Schemas\OnlineExamForm;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Tables\OnlineExamsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class OnlineExamResource extends Resource
{
    protected static ?string $model = OnlineExam::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Exams->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('exams::exams.exam.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('exams::exams.exam.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('questions');
    }

    public static function form(Schema $schema): Schema
    {
        return OnlineExamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnlineExamsTable::configure($table);
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
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOnlineExams::route('/'),
            'create' => Pages\CreateOnlineExam::route('/create'),
            'edit' => Pages\EditOnlineExam::route('/{record}/edit'),
        ];
    }
}
