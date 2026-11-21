<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Queries;

use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Storefront\Application\DataObjects\SiteSettingsData;

/**
 * إعدادات الموقع العام للمؤسسة الحالية (`tenant_settings`, المجموعة `site`).
 *
 * القارئ الوحيد لكل حقل في `ManageSiteSettings` — حقل بيتحفظ ومحدش بيقراه
 * نفس فخ «الإخفاء UX» (docs/24 بند ٦).
 */
final class SiteSettings
{
    public const GROUP = 'site';

    private ?SiteSettingsData $loaded = null;

    public function __construct(private readonly TenantSettings $settings) {}

    /**
     * كل المفاتيح وقيمتها لو المدرس ماغيّرهاش.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'platform_name' => null,
            'teacher_name' => null,
            'subject' => null,
            'hero_headline' => null,
            'hero_description' => null,
            'teacher_photo' => null,
            'hero_chip_1' => null,
            'hero_chip_2' => null,
            'hero_chip_3' => null,
            'about_text' => null,
            'show_stats' => true,
            'facebook_url' => null,
            'youtube_url' => null,
            'tiktok_url' => null,
            'instagram_url' => null,
            'telegram_url' => null,
            'whatsapp_number' => null,
            'floating_whatsapp' => true,
            'primary_color' => config('storefront.defaults.primary_color'),
            'secondary_color' => config('storefront.defaults.secondary_color'),
            'font_family' => config('storefront.defaults.font_family'),
            'footer_text' => null,
            'parent_phone_required' => false,
            'ask_governorate' => true,
            'ask_school_type' => true,
        ];
    }

    /** @return array<string, mixed> القيم الحالية (للفورم) */
    public function values(): array
    {
        $values = [];

        foreach (self::defaults() as $key => $default) {
            $values[$key] = $this->settings->get(self::GROUP, $key, $default);
        }

        return $values;
    }

    public function load(?string $tenantName = null): SiteSettingsData
    {
        return $this->loaded ??= SiteSettingsData::fromArray($this->values(), $tenantName);
    }

    public function flush(): void
    {
        $this->loaded = null;
    }
}
