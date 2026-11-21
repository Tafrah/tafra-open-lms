<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;

final class OnlinePassedExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('exams::exams.attempt.fields.student'))
                    ->description(fn (OnlinePassedExam $record): ?string => $record->student?->phone)
                    ->searchable(),
                TextColumn::make('exam.name')
                    ->label(__('exams::exams.attempt.fields.exam'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('student_total_mark')
                    ->label(__('exams::exams.attempt.fields.score'))
                    ->formatStateUsing(fn (OnlinePassedExam $record): string => fc_number((float) $record->student_total_mark).' / '.fc_number((float) $record->max_total_mark)),
                TextColumn::make('student_percentage')
                    ->label(__('exams::exams.attempt.fields.percentage'))
                    ->suffix('%')
                    ->color(fn (OnlinePassedExam $record): string => $record->passed() ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('grading_status')
                    ->label(__('exams::exams.attempt.fields.grading_status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d M Y — H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('finished', true))
            ->filters([
                SelectFilter::make('grading_status')
                    ->label(__('exams::exams.attempt.fields.grading_status'))
                    ->options(GradingStatus::class),
                SelectFilter::make('online_exam_id')
                    ->label(__('exams::exams.attempt.fields.exam'))
                    ->relationship('exam', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make()->label(fn (OnlinePassedExam $record): string => $record->grading_status === GradingStatus::Pending
                    ? __('exams::exams.attempt.actions.grade')
                    : __('exams::exams.attempt.actions.view')),
            ])
            ->emptyStateHeading(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results')
                : __('exams::exams.attempt.empty.heading'))
            ->emptyStateDescription(fn (Table $table): string => $table->isFiltered()
                ? __('table.empty.no_results_description')
                : __('exams::exams.attempt.empty.description'));
    }
}
