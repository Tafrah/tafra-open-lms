<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource;

final class CreateBootcamp extends CreateRecord
{
    protected static string $resource = BootcampResource::class;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'user_id' => auth()->id()];
    }
}
