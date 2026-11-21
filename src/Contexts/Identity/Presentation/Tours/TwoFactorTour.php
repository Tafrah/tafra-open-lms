<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Identity\Presentation\Filament\Pages\TwoFactorSettings;

/** صفحة الحساب مشتركة بين اللوحتين — جولة واحدة للاتنين. */
final class TwoFactorTour extends Tour
{
    private const PREFIX = 'identity::identity.tours.two_factor';

    public static function key(): string
    {
        return 'account.two_factor';
    }

    public static function page(): string
    {
        return TwoFactorSettings::class;
    }

    public static function panels(): array
    {
        return ['admin', 'central'];
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'purpose', '.fi-header-heading'),
            self::step(self::PREFIX, 'setup', '.fi-main'),
        ];
    }
}
