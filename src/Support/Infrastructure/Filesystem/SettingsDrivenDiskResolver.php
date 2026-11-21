<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Filesystem;

use Src\Contexts\Settings\Domain\Settings\StorageSettings;
use Src\Support\Application\Contracts\DiskResolver;

/**
 * الديسك قرار وقت تشغيل. مفيش 's3' ولا 'local' مكتوبين في أي كود
 * بره الكونفيج والكلاس ده (docs/04 بند ١٠).
 */
final class SettingsDrivenDiskResolver implements DiskResolver
{
    /** الديسك اللي بنرجع له لو الإعدادات لسه مش موجودة (أول migrate) */
    private const FALLBACK_DISK = 'public';

    private const FALLBACK_PRIVATE_DISK = 'local';

    public function for(string $collection): string
    {
        $settings = $this->settings();

        if ($settings === null) {
            return $this->isPrivate($collection) ? self::FALLBACK_PRIVATE_DISK : self::FALLBACK_DISK;
        }

        // ١. تجاوز صريح لمجموعة معيّنة من الإعدادات
        if (filled($override = $settings->collection_disks[$collection] ?? null)) {
            return $this->existing((string) $override);
        }

        // ٢. المجموعات الخاصة
        if ($this->isPrivate($collection)) {
            return $this->existing($settings->private_disk);
        }

        // ٣. الافتراضي
        return $this->existing($settings->default_disk);
    }

    public function forConversions(string $collection): string
    {
        $settings = $this->settings();

        if ($settings === null || blank($settings->conversions_disk)) {
            return $this->for($collection);
        }

        // التحويلات دايماً على ديسك عام — المصغّرات مش سرية
        return $this->existing($settings->conversions_disk);
    }

    public function isPrivate(string $collection): bool
    {
        $settings = $this->settings();

        if ($settings === null) {
            return in_array($collection, ['documents', 'contracts', 'identity_papers', 'receipt', 'answer_images'], true);
        }

        return in_array($collection, $settings->private_collections, true);
    }

    private function settings(): ?StorageSettings
    {
        try {
            return app(StorageSettings::class);
        } catch (\Throwable) {
            return null;
        }
    }

    /** ديسك محذوف من الكونفيج مايكسرش الرفع — بنرجع للافتراضي بهدوء. */
    private function existing(string $disk): string
    {
        return array_key_exists($disk, (array) config('filesystems.disks', []))
            ? $disk
            : self::FALLBACK_DISK;
    }
}
