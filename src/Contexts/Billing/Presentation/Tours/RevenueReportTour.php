<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Billing\Presentation\Filament\Pages\RevenueReport;

final class RevenueReportTour extends Tour
{
    private const PREFIX = 'billing::billing.tours.report';

    public static function key(): string
    {
        return 'billing.report';
    }

    public static function page(): string
    {
        return RevenueReport::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'range', '.fc-revenue-range'),
            self::step(self::PREFIX, 'stats', '.fc-revenue-stats'),
            self::step(self::PREFIX, 'months', '.fc-revenue-months'),
        ];
    }
}
