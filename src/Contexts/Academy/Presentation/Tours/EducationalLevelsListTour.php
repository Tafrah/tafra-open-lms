<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource\Pages\ManageEducationalLevels;

final class EducationalLevelsListTour extends Tour
{
    private const PREFIX = 'academy::academy.tours.level';

    public static function key(): string
    {
        return 'academy.level';
    }

    public static function page(): string
    {
        return ManageEducationalLevels::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
            self::step(self::PREFIX, 'create', '.fi-header .fi-ac'),
        ];
    }
}
