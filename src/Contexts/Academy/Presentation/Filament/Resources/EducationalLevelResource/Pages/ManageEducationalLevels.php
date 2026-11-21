<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource;

final class ManageEducationalLevels extends ManageRecords
{
    protected static string $resource = EducationalLevelResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
