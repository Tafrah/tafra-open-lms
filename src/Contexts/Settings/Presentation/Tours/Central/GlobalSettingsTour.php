<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Presentation\Tours\Central;

use FutureCode\Onboarding\Tours\Tour;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageAppearance;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageGeneral;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageMail;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageNotifications;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageSecurity;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageStorage;

/** كل تبويبات الإعدادات العامة نفس الشكل — جولة واحدة بتظهر على أول تبويب يتفتح. */
final class GlobalSettingsTour extends Tour
{
    private const PREFIX = 'settings::settings.tours.global';

    public static function key(): string
    {
        return 'settings.global';
    }

    public static function page(): string
    {
        return ManageGeneral::class;
    }

    public static function pages(): array
    {
        return [
            ManageGeneral::class,
            ManageAppearance::class,
            ManageStorage::class,
            ManageMail::class,
            ManageSecurity::class,
            ManageNotifications::class,
        ];
    }

    public static function panels(): array
    {
        return ['central'];
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
