<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Storefront\Application\Queries\LandingStats;
use Src\Contexts\Storefront\Application\Queries\SiteSettings;

/**
 * المدرس بيحفظ إعدادات الموقع العام. المفاتيح المعروفة بس بتتكتب — أي مفتاح
 * زيادة جاي من الفورم بيتشال (مفيش mass assignment على الإعدادات).
 */
final readonly class UpdateSiteSettingsAction
{
    public function __construct(
        private TenantSettings $settings,
        private SiteSettings $site,
    ) {}

    /** @param array<string, mixed> $values */
    public function handle(array $values, User $actor): void
    {
        Gate::forUser($actor)->authorize('access.site_settings');

        foreach (array_keys(SiteSettings::defaults()) as $key) {
            if (array_key_exists($key, $values)) {
                $this->settings->set(SiteSettings::GROUP, $key, $values[$key]);
            }
        }

        $this->site->flush();
        Cache::forget(LandingStats::cacheKey());
    }
}
