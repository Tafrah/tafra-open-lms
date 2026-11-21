<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource\Pages\ListBootcampSections;

final class BootcampSectionsListTour extends Tour
{
    private const PREFIX = 'academy::academy.tours.section';

    public static function key(): string
    {
        return 'academy.section';
    }

    public static function page(): string
    {
        return ListBootcampSections::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
        ];
    }
}
