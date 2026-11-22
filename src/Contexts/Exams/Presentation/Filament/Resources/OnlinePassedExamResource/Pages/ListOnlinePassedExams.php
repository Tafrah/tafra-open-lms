<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Exams\Infrastructure\Exports\OnlinePassedExamExporter;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource;

final class ListOnlinePassedExams extends ListRecords
{
    protected static string $resource = OnlinePassedExamResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label(__('exams::exams.attempt.actions.export'))
                ->exporter(OnlinePassedExamExporter::class)
                ->authorize('export'),
        ];
    }
}
