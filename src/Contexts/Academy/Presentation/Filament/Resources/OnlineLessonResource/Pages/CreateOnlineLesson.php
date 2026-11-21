<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Schemas\OnlineLessonForm;

final class CreateOnlineLesson extends CreateRecord
{
    protected static string $resource = OnlineLessonResource::class;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OnlineLessonForm::clearOtherTypes([...$data, 'user_id' => auth()->id()]);
    }
}
