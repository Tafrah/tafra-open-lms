<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Billing\Presentation\Filament\RelationManagers\StudentInvoicesRelationManager;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Schemas\StudentForm;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Tables\StudentsTable;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;

final class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Students->getLabel();
    }

    public static function getModelLabel(): string
    {
        return __('students::students.student.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('students::students.student.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('level:id,name');
    }

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'code'];
    }

    /** @return array<int, class-string> */
    public static function getRelations(): array
    {
        return [
            RelationManagers\EnrollmentsRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\AttemptsRelationManager::class,
            StudentInvoicesRelationManager::class,
        ];
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
