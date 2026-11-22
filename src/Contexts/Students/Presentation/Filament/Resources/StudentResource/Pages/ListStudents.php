<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Students\Infrastructure\Exports\StudentExporter;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource;

final class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // Excel أو CSV — في الطابور، والرابط بيوصل في الإشعارات
            ExportAction::make()
                ->label(__('students::students.student.actions.export'))
                ->exporter(StudentExporter::class)
                ->authorize('export'),
        ];
    }
}
