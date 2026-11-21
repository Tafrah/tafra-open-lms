<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Theming;

use InvalidArgumentException;

/**
 * تحويلات ألوان — sRGB ↔ OKLab ↔ OKLCH، وحساب التباين.
 *
 * OKLCH مختار عن قصد: التغيير في الإضاءة فيه إدراكياً متساوٍ،
 * فسُلَّم مولّد منه بيبان متدرّج بانتظام للعين — عكس HSL.
 */
final class Color
{
    /** @return array{0: float, 1: float, 2: float} sRGB 0..1 */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new InvalidArgumentException("قيمة لون غير صالحة: {$hex}");
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    public static function rgbToHex(float $r, float $g, float $b): string
    {
        $to = static fn (float $c): int => (int) round(max(0.0, min(1.0, $c)) * 255);

        return sprintf('#%02X%02X%02X', $to($r), $to($g), $to($b));
    }

    public static function isValidHex(string $hex): bool
    {
        return (bool) preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim($hex));
    }

    /** @return array{l: float, c: float, h: float} */
    public static function hexToOklch(string $hex): array
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        [$L, $a, $bb] = self::linearRgbToOklab(
            self::toLinear($r),
            self::toLinear($g),
            self::toLinear($b),
        );

        $chroma = sqrt($a ** 2 + $bb ** 2);
        $hue = $chroma < 1e-7 ? 0.0 : rad2deg(atan2($bb, $a));

        if ($hue < 0) {
            $hue += 360;
        }

        return ['l' => $L, 'c' => $chroma, 'h' => $hue];
    }

    public static function oklchToHex(float $l, float $c, float $h): string
    {
        $a = $c * cos(deg2rad($h));
        $b = $c * sin(deg2rad($h));

        [$lr, $lg, $lb] = self::oklabToLinearRgb($l, $a, $b);

        return self::rgbToHex(
            self::toGamma($lr),
            self::toGamma($lg),
            self::toGamma($lb),
        );
    }

    /** نسبة التباين حسب WCAG 2.1 — من 1:1 لـ 21:1 */
    public static function contrastRatio(string $hexA, string $hexB): float
    {
        $l1 = self::relativeLuminance($hexA);
        $l2 = self::relativeLuminance($hexB);

        [$light, $dark] = $l1 >= $l2 ? [$l1, $l2] : [$l2, $l1];

        return ($light + 0.05) / ($dark + 0.05);
    }

    public static function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        return 0.2126 * self::toLinear($r)
            + 0.7152 * self::toLinear($g)
            + 0.0722 * self::toLinear($b);
    }

    /** @return array{0: float, 1: float, 2: float} */
    private static function linearRgbToOklab(float $r, float $g, float $b): array
    {
        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        $l_ = self::cbrt($l);
        $m_ = self::cbrt($m);
        $s_ = self::cbrt($s);

        return [
            0.2104542553 * $l_ + 0.7936177850 * $m_ - 0.0040720468 * $s_,
            1.9779984951 * $l_ - 2.4285922050 * $m_ + 0.4505937099 * $s_,
            0.0259040371 * $l_ + 0.7827717662 * $m_ - 0.8086757660 * $s_,
        ];
    }

    /** @return array{0: float, 1: float, 2: float} */
    private static function oklabToLinearRgb(float $L, float $a, float $b): array
    {
        $l_ = $L + 0.3963377774 * $a + 0.2158037573 * $b;
        $m_ = $L - 0.1055613458 * $a - 0.0638541728 * $b;
        $s_ = $L - 0.0894841775 * $a - 1.2914855480 * $b;

        $l = $l_ ** 3;
        $m = $m_ ** 3;
        $s = $s_ ** 3;

        return [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
        ];
    }

    private static function cbrt(float $value): float
    {
        return $value < 0 ? -((-$value) ** (1 / 3)) : $value ** (1 / 3);
    }

    private static function toLinear(float $c): float
    {
        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    private static function toGamma(float $c): float
    {
        $c = max(0.0, min(1.0, $c));

        return $c <= 0.0031308 ? $c * 12.92 : 1.055 * ($c ** (1 / 2.4)) - 0.055;
    }
}
