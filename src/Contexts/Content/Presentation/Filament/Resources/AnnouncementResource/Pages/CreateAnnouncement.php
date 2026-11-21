<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource;

final class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    /**
     * الكاتب بيتحط برمجياً — مش من الفورم، عشان محدش يقدر ينسبه لحد تاني.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();

        return $data;
    }
}
