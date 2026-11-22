<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Transfer\Presentation\Filament\Pages\TafraFeatures;

final class TafraFeaturesTour extends Tour
{
    private const PREFIX = 'transfer::transfer.tours.tafra';

    public static function key(): string
    {
        return 'transfer.tafra';
    }

    public static function page(): string
    {
        return TafraFeatures::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'contact', '[data-tafra-feature]'),
        ];
    }
}
