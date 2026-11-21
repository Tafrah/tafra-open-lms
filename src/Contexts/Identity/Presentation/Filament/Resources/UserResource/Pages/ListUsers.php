<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\UserResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Identity\Infrastructure\Exports\UserExporter;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->exporter(UserExporter::class)
                ->authorize('export'),
        ];
    }
}
