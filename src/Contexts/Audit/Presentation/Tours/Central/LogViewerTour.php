<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Tours\Central;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Audit\Presentation\Filament\Central\Pages\LogViewer;

final class LogViewerTour extends Tour
{
    private const PREFIX = 'audit::audit.tours.logs';

    public static function key(): string
    {
        return 'system.logs';
    }

    public static function page(): string
    {
        return LogViewer::class;
    }

    public static function panels(): array
    {
        return ['central'];
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'viewer', '.fi-main'),
        ];
    }
}
