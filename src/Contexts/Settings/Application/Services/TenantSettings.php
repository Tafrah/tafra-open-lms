<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Application\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Src\Contexts\Settings\Domain\Models\TenantSetting;
use Src\Contexts\Settings\Domain\Settings\AppearanceSettings;
use Src\Contexts\Settings\Domain\Settings\GeneralSettings;
use Src\Contexts\Settings\Domain\Settings\MailSettings;
use Src\Contexts\Settings\Domain\Settings\NotificationSettings;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;
use Src\Contexts\Settings\Domain\Settings\StorageSettings;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Exceptions\MissingTenantContextException;

/**
 * إعدادات على مستوى المؤسسة مع fallback للإعداد العام.
 *
 * القاعدة: المؤسسة تقدر تغيّر **المظهر والإشعارات ووصول الذكاء الاصطناعي** بس.
 * التخزين والبريد والأمان عامة — يديرها الـ super_admin (docs/05 بند ٤).
 */
final readonly class TenantSettings
{
    /**
     * المجموعات المسموح للمؤسسة تعدّلها. `site` = إعدادات الموقع العام للطلبة
     * (Storefront — بتتكتب من `UpdateSiteSettingsAction` بصلاحية `access.site_settings`).
     */
    public const TENANT_EDITABLE_GROUPS = ['appearance', 'notifications', 'ai', 'site'];

    public function __construct(
        private TenantContext $context,
        private CacheRepository $cache,
    ) {}

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $tenantId = $this->context->id();

        if ($tenantId === null) {
            return $this->global($group, $key, $default);
        }

        return $this->cache->rememberForever(
            $this->cacheKey($tenantId, $group, $key),
            fn (): mixed => TenantSetting::query()
                ->where('tenant_id', $tenantId)
                ->where('group', $group)
                ->where('key', $key)
                ->value('value') ?? $this->global($group, $key, $default),
        );
    }

    public function set(string $group, string $key, mixed $value): void
    {
        $tenantId = $this->context->id();

        if ($tenantId === null) {
            throw new MissingTenantContextException(TenantSetting::class);
        }

        if (! in_array($group, self::TENANT_EDITABLE_GROUPS, true)) {
            throw new \InvalidArgumentException("مجموعة الإعدادات [{$group}] عامة — مش قابلة للتعديل لكل مؤسسة.");
        }

        TenantSetting::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'group' => $group, 'key' => $key],
            ['value' => $value],
        );

        $this->forget($tenantId, $group, $key);
    }

    public function forget(int $tenantId, string $group, string $key): void
    {
        $this->cache->forget($this->cacheKey($tenantId, $group, $key));
    }

    private function cacheKey(int $tenantId, string $group, string $key): string
    {
        return "tenant-settings:{$tenantId}:{$group}:{$key}";
    }

    private function global(string $group, string $key, mixed $default): mixed
    {
        $class = match ($group) {
            'appearance' => AppearanceSettings::class,
            'general' => GeneralSettings::class,
            'notifications' => NotificationSettings::class,
            'storage' => StorageSettings::class,
            'security' => SecuritySettings::class,
            'mail' => MailSettings::class,
            default => null,
        };

        if ($class === null) {
            return $default;
        }

        $settings = app($class);

        return property_exists($settings, $key) ? $settings->{$key} : $default;
    }
}
