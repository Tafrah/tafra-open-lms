<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Identity\Presentation\Filament\Pages\MyDevices;

final class MyDevicesTour extends Tour
{
    private const PREFIX = 'identity::identity.tours.devices';

    public static function key(): string
    {
        return 'account.devices';
    }

    public static function page(): string
    {
        return MyDevices::class;
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
