<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource;

final class EditBootcamp extends EditRecord
{
    protected static string $resource = BootcampResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
