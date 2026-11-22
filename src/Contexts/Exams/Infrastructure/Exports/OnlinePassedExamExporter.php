<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Infrastructure\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Notifications\Infrastructure\Exports\BaseExporter;

/**
 * نتايج الامتحانات (المحاولات المسلّمة) — في الطابور زي باقي التصدير،
 * والفلاتر المختارة في الجدول (امتحان معيّن مثلًا) بتتطبق على الملف.
 */
final class OnlinePassedExamExporter extends BaseExporter
{
    protected static ?string $model = OnlinePassedExam::class;

    public static function resourceLabel(): string
    {
        return __('exams::exams.attempt.plural');
    }

    /** @return array<int, ExportColumn> */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('student.name')->label(__('exams::exams.attempt.fields.student')),
            ExportColumn::make('student.phone')->label(__('exams::exams.attempt.export.phone')),
            ExportColumn::make('exam.name')->label(__('exams::exams.attempt.fields.exam')),
            ExportColumn::make('student_total_mark')->label(__('exams::exams.attempt.fields.score')),
            ExportColumn::make('max_total_mark')->label(__('exams::exams.attempt.export.max')),
            ExportColumn::make('student_percentage')->label(__('exams::exams.attempt.fields.percentage')),
            ExportColumn::make('passed')
                ->label(__('exams::exams.attempt.export.passed'))
                ->state(fn (OnlinePassedExam $record): string => $record->passed()
                    ? __('exams::exams.attempt.export.yes')
                    : __('exams::exams.attempt.export.no')),
            ExportColumn::make('end_at')->label(__('exams::exams.attempt.export.finished_at')),
        ];
    }

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->where('finished', true)->with(['student:id,name,phone', 'exam:id,name,min_percentage']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return trans_choice('exams::exams.attempt.export.completed', $export->successful_rows, [
            'count' => fc_number($export->successful_rows),
        ]);
    }
}
