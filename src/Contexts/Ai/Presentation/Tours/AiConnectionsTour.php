<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Ai\Presentation\Filament\Pages\AiConnections;

final class AiConnectionsTour extends Tour
{
    private const PREFIX = 'ai::ai.tours.connections';

    public static function key(): string
    {
        return 'ai.connections';
    }

    public static function page(): string
    {
        return AiConnections::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            // الإرشادات بتظهر بس للي يقدر يربط
            self::step(self::PREFIX, 'connect', '#ai-connect')->ability('access.ai'),
            self::step(self::PREFIX, 'table', '.fi-ta-ctn'),
        ];
    }
}
