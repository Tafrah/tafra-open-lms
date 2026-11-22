<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Infrastructure\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Notifications\Infrastructure\Exports\BaseExporter;
use Src\Contexts\Students\Domain\Enums\SchoolType;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * تصدير الطلبة Excel/CSV — في الطابور، والمدرس بياخد إشعار بالرابط (docs/08 بند ٦).
 * «الكورسات» = المشترك فيها دلوقتي (نفس `BootcampAccess` اللي بيفتح الكورس).
 */
final class StudentExporter extends BaseExporter
{
    protected static ?string $model = Student::class;

    public static function resourceLabel(): string
    {
        return __('students::students.student.plural');
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('level:id,name');
    }

    /** @return array<int, ExportColumn> */
    public static function getColumns(): array
    {
        $label = static fn (string $key): string => __("students::students.student.fields.{$key}");

        return [
            ExportColumn::make('name')->label($label('name')),
            ExportColumn::make('phone')->label($label('phone')),
            ExportColumn::make('parent_phone')->label($label('parent_phone')),
            ExportColumn::make('level.name')->label($label('level')),
            ExportColumn::make('school_type')
                ->label($label('school_type'))
                ->formatStateUsing(fn (mixed $state): string => $state instanceof SchoolType ? $state->getLabel() : (string) $state),
            ExportColumn::make('balance')->label($label('balance')),
            ExportColumn::make('enrolled_courses')
                ->label($label('enrolled_courses'))
                ->state(fn (Student $record): int => count(app(BootcampAccess::class)->bootcampIds($record))),
            ExportColumn::make('created_at')->label(__('common.created_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return trans_choice('students::students.student.export.completed', $export->successful_rows, [
            'count' => fc_number($export->successful_rows),
        ]);
    }
}
