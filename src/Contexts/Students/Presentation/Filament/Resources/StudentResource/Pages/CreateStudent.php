<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Students\Application\Actions\RegisterStudentAction;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource;

final class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $data['phone'] = RegisterStudentAction::normalizePhone((string) $data['phone']);

        return app(RegisterStudentAction::class)->handle($data);
    }
}
