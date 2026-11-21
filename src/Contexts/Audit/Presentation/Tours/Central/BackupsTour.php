<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Tours\Central;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Audit\Presentation\Filament\Central\Pages\Backups;

final class BackupsTour extends Tour
{
    private const PREFIX = 'audit::audit.tours.backups';

    public static function key(): string
    {
        return 'system.backups';
    }

    public static function page(): string
    {
        return Backups::class;
    }

    public static function panels(): array
    {
        return ['central'];
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'run', '.fi-header .fi-ac'),
            self::step(self::PREFIX, 'list', '.fi-main'),
        ];
    }
}
