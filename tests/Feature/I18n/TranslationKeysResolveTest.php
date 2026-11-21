<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * كل مفتاح ترجمة **ثابت** مستخدم في الكود لازم يرجّع نص فعلي.
 *
 * اختبار التطابق (ar ↔ en) بيمسك المفاتيح الناقصة من ملف؛ الاختبار ده
 * بيمسك النوع التاني من الغلط: مفتاح متكتب في الكود وأصلاً مش موجود
 * في أي ملف — الخطأ المطبعي اللي بيوصل للمستخدم كنص خام.
 */

/** @return array<string, list<string>> [key => files] */
function staticTranslationKeys(): array
{
    $keys = [];
    $roots = [src_path(), app_path(), resource_path('views'), base_path('packages')];

    foreach ($roots as $root) {
        if (! File::isDirectory($root)) {
            continue;
        }

        foreach (File::allFiles($root) as $file) {
            if (! in_array($file->getExtension(), ['php'], true)) {
                continue;
            }

            $contents = $file->getExtension() === 'php' && ! str_ends_with($file->getFilename(), '.blade.php')
                ? phpCodeWithoutComments($file->getContents())
                : $file->getContents();

            // __('key') و trans_choice('key') — الحرفية بس، بدون تركيب
            preg_match_all("/(?:__|trans|trans_choice)\(\s*'([a-zA-Z0-9_.:\\-]+)'/", $contents, $found);

            foreach ($found[1] as $key) {
                // مفتاح من غير نقطة = مفتاح JSON، مش مفتاح ملف
                if (! str_contains($key, '.')) {
                    continue;
                }

                // مفتاح منتهي بنقطة معناه `__('prefix.'.$variable)` —
                // بيتبني وقت التشغيل فمش قابل للفحص الساكن
                if (str_ends_with($key, '.')) {
                    continue;
                }

                $keys[$key][] = $file->getRelativePathname();
            }
        }
    }

    return $keys;
}

it('كل مفتاح ترجمة ثابت في الكود بيرجّع نص', function (string $locale): void {
    app()->setLocale($locale);

    $unresolved = [];

    foreach (staticTranslationKeys() as $key => $files) {
        $value = __($key);

        // الترجمة الناقصة بترجّع المفتاح نفسه
        if ($value === $key) {
            $unresolved[] = "{$key}  ←  ".implode(', ', array_unique(array_slice($files, 0, 2)));
        }
    }

    app()->setLocale(config('app.locale'));

    expect($unresolved)->toBeEmpty(
        "مفاتيح ترجمة مستخدمة في الكود ومش موجودة ({$locale}):\n".implode("\n", $unresolved),
    );
})->with(['ar', 'en']);

it('كل عرض مُشار إليه في الكود موجود', function (): void {
    $missing = [];
    $roots = [src_path(), app_path(), base_path('packages')];

    foreach ($roots as $root) {
        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            preg_match_all("/view\(\s*'([a-zA-Z0-9_.:\\-]+)'/", $file->getContents(), $found);

            foreach ($found[1] as $view) {
                if (! view()->exists($view)) {
                    $missing[] = "{$view}  ←  {$file->getRelativePathname()}";
                }
            }
        }
    }

    // العروض المشار إليها من Filament Pages عبر `protected string $view`
    foreach (allPhpFiles([src_path(), base_path('packages')]) as $file) {
        if (preg_match("/protected string \\\$view = '([a-zA-Z0-9_.:\\-]+)'/", $file->getContents(), $found)) {
            if (! view()->exists($found[1])) {
                $missing[] = "{$found[1]}  ←  {$file->getRelativePathname()}";
            }
        }
    }

    expect($missing)->toBeEmpty("عروض مش موجودة:\n".implode("\n", $missing));
});

it('كل كلاس مُشار إليه في الكونفيج موجود', function (): void {
    $missing = [];

    // كونفيجاتنا بس — ملفات الباكدجات المنشورة بتشاور على كلاسات من
    // إصدارات مدفوعة (MediaLibraryPro مثلاً) مش مثبّتة عندنا عن قصد.
    $configs = ['authorization', 'security', 'content', 'notifications', 'seeding', 'settings', 'fc-onboarding'];

    foreach ($configs as $name) {
        $walk = function (mixed $value, string $path) use (&$walk, &$missing): void {
            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $walk($item, "{$path}.{$key}");
                }

                return;
            }

            if (! is_string($value) || ! str_contains($value, '\\')) {
                return;
            }

            if (! class_exists($value) && ! interface_exists($value) && ! enum_exists($value)) {
                $missing[] = "{$path} → {$value}";
            }
        };

        $walk(config($name, []), $name);
    }

    expect($missing)->toBeEmpty("كلاسات مش موجودة:\n".implode("\n", $missing));
});
