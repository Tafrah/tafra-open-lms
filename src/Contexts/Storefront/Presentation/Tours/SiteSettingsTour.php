<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Storefront\Presentation\Filament\Pages\ManageSiteSettings;

final class SiteSettingsTour extends Tour
{
    private const PREFIX = 'storefront::storefront.tours.site_settings';

    public static function key(): string
    {
        return 'storefront.site_settings';
    }

    public static function page(): string
    {
        return ManageSiteSettings::class;
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'tabs', '.fi-page-sub-navigation-tabs'),
            self::step(self::PREFIX, 'form', '#form'),
            self::step(self::PREFIX, 'save', '#form .fi-sc-actions'),
        ];
    }
}
