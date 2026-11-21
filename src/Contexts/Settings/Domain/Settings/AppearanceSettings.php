<?php

declare(strict_types=1);

namespace Src\Contexts\Settings\Domain\Settings;

use Spatie\LaravelSettings\Settings;

final class AppearanceSettings extends Settings
{
    public string $primary_color;

    public ?string $logo_light_path;

    public ?string $logo_dark_path;

    public ?string $favicon_path;

    public string $default_theme;

    public bool $allow_theme_switch;

    public string $sidebar_default;

    /** @var array<string, string> */
    public array $footer_text;

    public string $font_family;

    public static function group(): string
    {
        return 'appearance';
    }

    /** @return list<string> */
    public static function encrypted(): array
    {
        return [];
    }
}
