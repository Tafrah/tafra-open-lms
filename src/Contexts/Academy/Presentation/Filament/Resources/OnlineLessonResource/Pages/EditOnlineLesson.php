<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource\Schemas\OnlineLessonForm;

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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return OnlineLessonForm::clearOtherTypes($data);
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record instanceof OnlineLesson) {
            OnlineLessonForm::clearDocumentsUnlessPdf($record);
        }
    }
}
