<?php

declare(strict_types=1);

use Src\Support\Infrastructure\Theming\Color;
use Src\Support\Infrastructure\Theming\ColorScaleGenerator;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;

/** السُلَّم المعتمد في دليل الهوية — المولّد لازم يعيد إنتاجه */
const BRAND_SCALE = [
    50 => '#EAF1F2', 100 => '#CFE0E2', 200 => '#A9C6CA', 300 => '#7FA5AB',
    400 => '#4E7A80', 500 => '#2D616A', 600 => '#12454F',
    700 => '#0E3841', 800 => '#0A2B32', 900 => '#071E23',
];

it('يعيد إنتاج سُلَّم العلامة المعتمد', function (): void {
    $scale = app(ColorScaleGenerator::class)->generate('#12454F');

    foreach (BRAND_SCALE as $shade => $expected) {
        // انحراف قناة واحدة مقبول — الاشتقاق رياضي مش جدول محفوظ
        $delta = max(array_map(
            static fn (float $a, float $b): float => abs($a - $b) * 255,
            Color::hexToRgb($scale[$shade]),
            Color::hexToRgb($expected),
        ));

        expect($delta)->toBeLessThanOrEqual(6.0, "الدرجة {$shade}: {$scale[$shade]} مقابل {$expected}");
    }
});

it('يولّد سُلَّماً مترتّباً لأي لون', function (string $hex): void {
    $scale = app(ColorScaleGenerator::class)->generate($hex);
    $previous = 1.1;

    foreach ($scale as $shade => $value) {
        $luminance = Color::relativeLuminance($value);

        expect($luminance)->toBeLessThanOrEqual($previous + 1e-9, "الدرجة {$shade} أفتح من اللي قبلها");

        $previous = $luminance;
    }
})->with(['#12454F', '#B4232E', '#2E5AAC', '#5D7A00', '#9B2C6F', '#A55A00', '#000000', '#FFFFFF']);

it('يرفض لوناً تباينه أقل من ٤٫٥:١ على أبيض', function (): void {
    $generator = app(ColorScaleGenerator::class);

    expect($generator->passesContrast('#12454F'))->toBeTrue()
        ->and($generator->passesContrast('#B4232E'))->toBeTrue()
        ->and($generator->passesContrast('#00FF00'))->toBeFalse()
        ->and($generator->passesContrast('#FFEE00'))->toBeFalse()
        // لون طفرة الافتراضي مسموح — من غيره ملف المؤسسة مايتحفظش بالقيم الافتراضية
        ->and($generator->passesContrast(ThemeColorResolver::DEFAULT_BRAND))->toBeTrue();
});

it('يحسب التباين حسب WCAG', function (): void {
    expect(round(Color::contrastRatio('#000000', '#FFFFFF'), 2))->toBe(21.0)
        ->and(round(Color::contrastRatio('#FFFFFF', '#FFFFFF'), 2))->toBe(1.0);
});

it('يحوّل ذهاباً وإياباً بين hex و OKLCH من غير فقد', function (string $hex): void {
    $oklch = Color::hexToOklch($hex);

    expect(Color::oklchToHex($oklch['l'], $oklch['c'], $oklch['h']))->toBe(strtoupper($hex));
})->with(['#12454F', '#B4232E', '#2E5AAC', '#5D7A00']);

it('يتحقق من صيغة اللون', function (): void {
    expect(Color::isValidHex('#12454F'))->toBeTrue()
        ->and(Color::isValidHex('12454F'))->toBeTrue()
        ->and(Color::isValidHex('#ABC'))->toBeTrue()
        ->and(Color::isValidHex('#12345'))->toBeFalse()
        ->and(Color::isValidHex('red'))->toBeFalse();
});

it('ألوان الحالة الفاتحة كلها تعدّي تباين ٤٫٥:١ على أبيض', function (): void {
    foreach (ThemeColorResolver::STATUS as $name => $hex) {
        expect(Color::contrastRatio($hex, '#FFFFFF'))
            ->toBeGreaterThanOrEqual(4.5, "لون الحالة {$name} تباينه ضعيف على أبيض");
    }
});
