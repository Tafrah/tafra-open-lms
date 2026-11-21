<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Theming;

use Illuminate\Support\Facades\Storage;
use Src\Contexts\Settings\Domain\Settings\AppearanceSettings;
use Src\Support\Application\Contracts\DiskResolver;

/** بيحوّل مسار اللوجو المخزّن في الإعدادات لرابط قابل للعرض. */
final class BrandLogoResolver
{
    public function __construct(
        private readonly DiskResolver $disks,
    ) {}

    public function url(bool $dark = false): ?string
    {
        try {
            $settings = app(AppearanceSettings::class);
        } catch (\Throwable) {
            return null;
        }

        $path = $dark
            ? ($settings->logo_dark_path ?? $settings->logo_light_path)
            : $settings->logo_light_path;

        if (blank($path)) {
            return null;
        }

        return Storage::disk($this->disks->for('branding'))->url($path);
    }

    public function faviconUrl(): ?string
    {
        try {
            $path = app(AppearanceSettings::class)->favicon_path;
        } catch (\Throwable) {
            return null;
        }

        return blank($path)
            ? null
            : Storage::disk($this->disks->for('branding'))->url($path);
    }
}
