<?php

declare(strict_types=1);

namespace Src\Contexts\Media\Presentation\Filament\Resources\MediaResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Media\Presentation\Filament\Resources\MediaResource;

final class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;
}
