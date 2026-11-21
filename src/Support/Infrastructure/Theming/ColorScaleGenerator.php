<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Theming;

/**
 * يولّد سُلَّم 50→900 من درجة 600 باستخدام OKLCH.
 *
 * المؤسسة بتضبط 600 بس؛ الباقي مُشتَق برمجياً (docs/06 بند ٢).
 *
 * المعاملات دي مستخرجة **قياساً** من سُلَّم البترولي المعتمد في دليل الهوية
 * (#12454F)، فالمولّد بيعيد إنتاجه بدقة، وبيتصرّف صح مع أي لون تاني.
 * الاشتقاق بالمزج ناحية نقطة فاتحة — مش بضرب ثابت — عشان السُلَّم يفضل
 * **مترتّب تصاعدياً** مهما كانت إضاءة اللون الأساسي.
 */
final class ColorScaleGenerator
{
    /** أعلى إضاءة ممكنة للدرجة 50 */
    private const LIGHT_ANCHOR = 0.97;

    /** نسبة المزج ناحية النقطة الفاتحة (الدرجات الأفتح من 600) */
    private const LIGHTNESS_BLEND = [
        50 => 0.972, 100 => 0.876, 200 => 0.731,
        300 => 0.549, 400 => 0.309, 500 => 0.162,
    ];

    /** معامل الإضاءة للدرجات الأغمق من 600 */
    private const DARK_FACTOR = [700 => 0.874, 800 => 0.743, 900 => 0.606, 950 => 0.455];

    /** نسبة التشبّع (chroma) من تشبّع اللون الأساسي */
    private const CHROMA_FALLOFF = [
        50 => 0.139, 100 => 0.336, 200 => 0.579, 300 => 0.771,
        400 => 0.899, 500 => 1.042, 600 => 1.0,
        700 => 0.868, 800 => 0.720, 900 => 0.552,
        // 950 مش في دليل الهوية — Filament v5 بيطلبها، فبنشتقّها بنفس المنحنى
        950 => 0.420,
    ];

    /** @return array<int, string> */
    public function generate(string $hex): array
    {
        $base = Color::hexToOklch($hex);
        $anchor = max(self::LIGHT_ANCHOR, $base['l'] + 0.02);

        $scale = [600 => '#'.strtoupper(ltrim(trim($hex), '#'))];

        foreach (self::LIGHTNESS_BLEND as $shade => $t) {
            $scale[$shade] = Color::oklchToHex(
                $base['l'] + ($anchor - $base['l']) * $t,
                $base['c'] * self::CHROMA_FALLOFF[$shade],
                $base['h'],
            );
        }

        foreach (self::DARK_FACTOR as $shade => $factor) {
            $scale[$shade] = Color::oklchToHex(
                $base['l'] * $factor,
                $base['c'] * self::CHROMA_FALLOFF[$shade],
                $base['h'],
            );
        }

        ksort($scale);

        return $scale;
    }

    /**
     * الدرجات 600 و700 لازم يعدّوا تباين ≥ 4.5:1 على أبيض
     * — وإلا النص الأبيض على الزرار الأساسي مش هيتقرا.
     */
    public function passesContrast(string $hex, float $minimum = 4.5): bool
    {
        return $this->worstContrast($hex) >= $minimum;
    }

    public function worstContrast(string $hex): float
    {
        $scale = $this->generate($hex);

        return min(
            Color::contrastRatio($scale[600], '#FFFFFF'),
            Color::contrastRatio($scale[700], '#FFFFFF'),
        );
    }
}
