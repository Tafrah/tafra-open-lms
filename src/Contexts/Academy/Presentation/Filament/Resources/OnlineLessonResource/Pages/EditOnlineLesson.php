<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource;

final class EditOnlineLesson extends EditRecord
{
    protected static string $resource = OnlineLessonResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
