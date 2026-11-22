<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Pages\ListEnrollments;

final class EnrollmentsListTour extends Tour
{
    private const PREFIX = 'billing::billing.tours.enrollment';

    public static function key(): string
    {
        return 'billing.enrollment';
    }

    public static function page(): string
    {
        return ListEnrollments::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'bulk', '.fi-header .fi-ac'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
        ];
    }
}
