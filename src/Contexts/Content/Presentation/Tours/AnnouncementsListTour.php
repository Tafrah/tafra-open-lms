<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;

final class AnnouncementsListTour extends Tour
{
    private const PREFIX = 'content::content.tours.announcements';

    public static function key(): string
    {
        return 'announcements.list';
    }

    public static function page(): string
    {
        return ListAnnouncements::class;
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
