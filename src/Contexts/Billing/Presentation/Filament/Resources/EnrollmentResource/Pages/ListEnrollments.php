<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource;

final class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;
}
