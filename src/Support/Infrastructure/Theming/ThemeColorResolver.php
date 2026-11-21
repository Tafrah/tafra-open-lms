<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Theming;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Src\Contexts\Settings\Domain\Settings\AppearanceSettings;

/**
 * الجسر بين إعداد اللون الواحد اللي المؤسسة بتضبطه وبين لوحة ألوان Filament.
 *
 * طبقة العلامة (primary) بتتغيّر لكل مؤسسة · طبقة المنتج ثابتة (docs/06 بند ١).
 */
final class ThemeColorResolver
{
    /** لون العلامة الافتراضي — بترولي كود المستقبل (درجة 600) */
    public const DEFAULT_BRAND = '#12454F';

    /** المحايدات البترولية بتاعتنا — مش رمادي Filament الافتراضي */
    public const BRAND_NEUTRAL = '#4E6260';

    /** ألوان الحالة — ثابتة، ممنوع تتغيّر لكل مؤسسة */
    public const STATUS = [
        'success' => '#0F7A3D',
        'warning' => '#96550A',
        'danger' => '#B4232E',
        'info' => '#0E6E8C',
    ];

    public function __construct(
        private readonly ColorScaleGenerator $generator,
    ) {}

    /** @return array<int, string> */
    public function scale(?string $hex = null): array
    {
        $hex ??= $this->primaryHex();

        return Cache::remember(
            'theme:scale:'.md5($hex),
            now()->addDay(),
            fn (): array => $this->generator->generate($hex),
        );
    }

    /** @return array<string, array<int, string>|string> */
    public function palette(): array
    {
        return [
            'primary' => $this->scale(),
            'gray' => $this->generator->generate(self::BRAND_NEUTRAL),
            ...array_map(
                fn (string $hex): array => $this->generator->generate($hex),
                self::STATUS,
            ),
        ];
    }

    /**
     * لون العلامة الفعّال.
     *
     * الترتيب مقصود: لون المؤسسة الحالية بيغلب الإعداد العام —
     * ده اللي بيخلّي «طبقة العلامة» تتغيّر لكل مستأجر فعلاً (docs/06 بند ١).
     */
    public function primaryHex(): string
    {
        $tenantColor = Filament::getTenant()?->getAttribute('primary_color');

        if (is_string($tenantColor) && Color::isValidHex($tenantColor)) {
            return $tenantColor;
        }

        try {
            $hex = app(AppearanceSettings::class)->primary_color;
        } catch (\Throwable) {
            // قبل ما الإعدادات تتزرع (أول migrate) — نرجع للافتراضي
            $hex = self::DEFAULT_BRAND;
        }

        return Color::isValidHex($hex) ? $hex : self::DEFAULT_BRAND;
    }

    public static function flushCache(): void
    {
        Cache::forget('theme:brand-variables');
    }
}
