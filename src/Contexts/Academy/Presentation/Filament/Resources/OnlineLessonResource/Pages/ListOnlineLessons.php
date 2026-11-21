<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource;

final class ListOnlineLessons extends ListRecords
{
    protected static string $resource = OnlineLessonResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
