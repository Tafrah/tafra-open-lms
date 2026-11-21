<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Tours\Central;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Audit\Presentation\Filament\Central\Pages\SystemHealth;

final class SystemHealthTour extends Tour
{
    private const PREFIX = 'audit::audit.tours.health';

    public static function key(): string
    {
        return 'system.health';
    }

    public static function page(): string
    {
        return SystemHealth::class;
    }

    public static function panels(): array
    {
        return ['central'];
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'refresh', '.fi-header .fi-ac'),
            self::step(self::PREFIX, 'results', '.fi-main'),
        ];
    }
}
