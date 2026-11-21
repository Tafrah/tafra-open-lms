<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;

/** السجل مسجّل في اللوحتين: مفلتر بالمؤسسة في `/admin`، وكامل في `/central`. */
final class ActivityLogTour extends Tour
{
    private const PREFIX = 'audit::audit.tours.activity';

    public static function key(): string
    {
        return 'audit.activity_log';
    }

    public static function page(): string
    {
        return ListActivityLogs::class;
    }

    public static function panels(): array
    {
        return ['admin', 'central'];
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
        ];
    }
}
