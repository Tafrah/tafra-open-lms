<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Notifications\Presentation\Filament\Pages\NotificationPreferences;

final class NotificationPreferencesTour extends Tour
{
    private const PREFIX = 'notifications::notifications.tours.preferences';

    public static function key(): string
    {
        return 'notifications.preferences';
    }

    public static function page(): string
    {
        return NotificationPreferences::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'matrix', '.fi-main'),
        ];
    }
}
