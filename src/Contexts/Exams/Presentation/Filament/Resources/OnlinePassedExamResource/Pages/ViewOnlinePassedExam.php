<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource;

final class ViewOnlinePassedExam extends ViewRecord
{
    protected static string $resource = OnlinePassedExamResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    TextEntry::make('student.name')
                        ->label(__('exams::exams.attempt.fields.student'))
                        ->helperText(fn (OnlinePassedExam $record): ?string => $record->student?->phone),
                    TextEntry::make('exam.name')
                        ->label(__('exams::exams.attempt.fields.exam')),
                    TextEntry::make('student_total_mark')
                        ->label(__('exams::exams.attempt.fields.score'))
                        ->formatStateUsing(fn (OnlinePassedExam $record): string => fc_number((float) $record->student_total_mark).' / '.fc_number((float) $record->max_total_mark)),
                    TextEntry::make('student_percentage')
                        ->label(__('exams::exams.attempt.fields.percentage'))
                        ->suffix('%'),
                    TextEntry::make('grading_status')
                        ->label(__('exams::exams.attempt.fields.grading_status'))
                        ->badge(),
                    TextEntry::make('current_student_chance')
                        ->label(__('exams::exams.attempt.fields.chance')),
                    TextEntry::make('start_at')
                        ->label(__('exams::exams.attempt.fields.start_at'))
                        ->dateTime('d M Y — H:i'),
                    TextEntry::make('end_at')
                        ->label(__('exams::exams.attempt.fields.end_at'))
                        ->dateTime('d M Y — H:i')
                        ->placeholder('—'),
                ]),
        ]);
    }
}
