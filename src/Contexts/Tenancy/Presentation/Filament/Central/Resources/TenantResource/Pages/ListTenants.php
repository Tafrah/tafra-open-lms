<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource;

final class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
