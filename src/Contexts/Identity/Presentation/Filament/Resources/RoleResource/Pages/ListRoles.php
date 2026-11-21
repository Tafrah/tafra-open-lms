<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource;

final class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
