<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Tours;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantAi;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantAppearance;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantNotifications;

final class OrganizationSettingsTour extends Tour
{
    private const PREFIX = 'settings::settings.tours.organization';

    public static function key(): string
    {
        return 'settings.organization';
    }

    public static function page(): string
    {
        return ManageTenantAppearance::class;
    }

    public static function pages(): array
    {
        return [ManageTenantAppearance::class, ManageTenantNotifications::class, ManageTenantAi::class];
    }

    public function steps(): array
    {
        return [
            self::step(self::PREFIX, 'tabs', '.fi-page-sub-navigation-tabs'),
            // `form` لوحده بيمسك فورم تسجيل الخروج المخفي في قائمة المستخدم — الفورم الأول في الصفحة
            self::step(self::PREFIX, 'form', '#form'),
            // Filament 5 مابيرندرش `.fi-form-actions` — الأزرار في `fi-sc-actions` جوه الفورم
            self::step(self::PREFIX, 'save', '#form .fi-sc-actions'),
        ];
    }
}
