<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource;

final class ListRechargeCardGroups extends ListRecords
{
    protected static string $resource = RechargeCardGroupResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
