<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource;

final class ListBootcampSections extends ListRecords
{
    protected static string $resource = BootcampSectionResource::class;
}
