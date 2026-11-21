<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource;

final class ListOnlineExams extends ListRecords
{
    protected static string $resource = OnlineExamResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
