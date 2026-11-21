<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** امتحانات الطالب. */
final class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('students::students.student.relations.attempts');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('exam:id,name')->where('finished', true)->latest('id'))
            ->columns([
                TextColumn::make('exam.name')->label(__('exams::exams.attempt.fields.exam'))->wrap(),
                TextColumn::make('student_percentage')->label(__('exams::exams.attempt.fields.percentage'))->suffix('%'),
                TextColumn::make('grading_status')->label(__('exams::exams.attempt.fields.grading_status'))->badge(),
                TextColumn::make('created_at')->label(__('common.created_at'))->dateTime('d M Y — H:i'),
            ])
            ->emptyStateHeading(__('students::students.student.relations.attempts'))
            ->emptyStateDescription(__('students::students.student.empty_related'));
    }
}
