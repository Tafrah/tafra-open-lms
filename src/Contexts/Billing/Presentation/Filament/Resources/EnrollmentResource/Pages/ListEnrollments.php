<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Billing\Presentation\Filament\Actions\BulkEnrollmentActions;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource;

final class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    /** @return array<int, Action|ActionGroup> */
    protected function getHeaderActions(): array
    {
        return [
            BulkEnrollmentActions::activate(),
            BulkEnrollmentActions::cancel(),
            BulkEnrollmentActions::enrollOne(),
        ];
    }
}
