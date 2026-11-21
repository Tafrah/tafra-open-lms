<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource;

final class EditOnlineExam extends EditRecord
{
    protected static string $resource = OnlineExamResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
