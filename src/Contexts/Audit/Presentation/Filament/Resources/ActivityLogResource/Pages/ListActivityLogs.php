<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource;

final class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
}
