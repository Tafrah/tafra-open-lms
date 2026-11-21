<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource;

final class ListOnlinePassedExams extends ListRecords
{
    protected static string $resource = OnlinePassedExamResource::class;
}
