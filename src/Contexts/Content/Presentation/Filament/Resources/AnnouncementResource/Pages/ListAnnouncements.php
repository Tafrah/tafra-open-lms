<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Content\Infrastructure\Exports\AnnouncementExporter;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource;

final class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->exporter(AnnouncementExporter::class)
                ->authorize('export'),
        ];
    }
}
