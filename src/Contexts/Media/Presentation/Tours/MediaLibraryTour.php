<?php

declare(strict_types=1);

namespace Src\Contexts\Media\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Media\Presentation\Filament\Resources\MediaResource\Pages\ListMedia;

final class MediaLibraryTour extends Tour
{
    private const PREFIX = 'media::media.tours.library';

    public static function key(): string
    {
        return 'media.library';
    }

    public static function page(): string
    {
        return ListMedia::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
        ];
    }
}
