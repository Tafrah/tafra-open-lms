<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource;

final class CreateOnlineExam extends CreateRecord
{
    protected static string $resource = OnlineExamResource::class;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'user_id' => auth()->id(), 'published' => false];
    }
}
