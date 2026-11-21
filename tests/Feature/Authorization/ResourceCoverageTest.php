<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * كل مورد في **كل لوحة** له ملف اختبار صلاحيات (docs/17 بند ٤).
 *
 * الاختبار ده هو اللي بيمنع «ضفنا مورد ونسينا نختبر صلاحياته» — وهي
 * أسهل غلطة تحصل، لأن المورد بيشتغل تمام والـ Policy بتتنادى، بس
 * محدش أثبت إن المصفوفة صح.
 */
it('كل مورد في اللوحتين له اختبار صلاحيات', function (string $panelId): void {
    $missing = [];

    foreach (Filament::getPanel($panelId)->getResources() as $resource) {
        // `UserResource` → `UserAuthorizationTest` — الاصطلاح باسم الموديل
        // زي المثال في docs/17 بند ٤ (مش `class_basename` الخام اللي في
        // عيّنة الاختبار هناك، وهي بتخالف اسم الملف اللي فوقيها بسطور).
        $name = Str::beforeLast(class_basename($resource), 'Resource');
        $path = base_path("tests/Feature/Authorization/{$name}AuthorizationTest.php");

        if (! File::exists($path)) {
            $missing[] = "{$name} → tests/Feature/Authorization/{$name}AuthorizationTest.php";
        }
    }

    expect($missing)->toBeEmpty("موارد من غير اختبار صلاحيات ({$panelId}):\n".implode("\n", $missing));
})->with(['admin', 'central']);
