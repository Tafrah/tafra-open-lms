<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Src\Support\Infrastructure\Authorization\PermissionBuilder;

/** @return array<int, array{0: string, 1: string, 2: string}> [label, arFile, enFile] */
function translationFilePairs(): array
{
    $pairs = [];

    foreach (File::files(lang_path('ar')) as $file) {
        $pairs[] = [
            "lang/{$file->getFilename()}",
            $file->getPathname(),
            lang_path('en/'.$file->getFilename()),
        ];
    }

    foreach (File::directories(src_path('Contexts')) as $context) {
        $arDir = $context.'/Lang/ar';

        if (! File::isDirectory($arDir)) {
            continue;
        }

        foreach (File::files($arDir) as $file) {
            $pairs[] = [
                basename($context)."/{$file->getFilename()}",
                $file->getPathname(),
                $context.'/Lang/en/'.$file->getFilename(),
            ];
        }
    }

    return $pairs;
}

it('كل ملف ترجمة عربي له مقابل إنجليزي بنفس المفاتيح', function (): void {
    $problems = [];

    foreach (translationFilePairs() as [$label, $arPath, $enPath]) {
        if (! File::exists($enPath)) {
            $problems[] = "ناقص بالكامل: {$label}";

            continue;
        }

        $arKeys = array_keys(Arr::dot(require $arPath));
        $enKeys = array_keys(Arr::dot(require $enPath));

        foreach (array_diff($arKeys, $enKeys) as $missing) {
            $problems[] = "ناقص في en/{$label}: {$missing}";
        }

        foreach (array_diff($enKeys, $arKeys) as $extra) {
            $problems[] = "زيادة في en/{$label}: {$extra}";
        }
    }

    expect($problems)->toBeEmpty("عدم تطابق الترجمة:\n".implode("\n", $problems));
});

it('كل صلاحية في الكونفيج لها تسمية مترجمة في اللغتين', function (): void {
    $problems = [];

    foreach (app(PermissionBuilder::class)->allPermissionNames() as $permission) {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            $label = permission_label($permission);

            if (str_contains($label, 'authorization.') || trim($label) === '') {
                $problems[] = "{$permission} غير مترجم في {$locale}";
            }
        }
    }

    app()->setLocale(config('app.locale'));

    expect($problems)->toBeEmpty(implode("\n", $problems));
});

it('كل رسالة رفض في السياسات لها ترجمة', function (): void {
    $problems = [];
    $keys = [];

    foreach (allPhpFiles([src_path('Contexts')]) as $file) {
        if (! str_contains($file->getPathname(), '/Policies/')) {
            continue;
        }

        preg_match_all("/->(?:rule|ruleUsing|ruleOrNotFound)\([^,]+,\s*'([a-z_.]+)'/", $file->getContents(), $found);
        $keys = [...$keys, ...$found[1]];
    }

    foreach (array_unique($keys) as $key) {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            $message = __("authorization.denied.{$key}");

            if ($message === "authorization.denied.{$key}") {
                $problems[] = "authorization.denied.{$key} غير مترجم في {$locale}";
            }
        }
    }

    app()->setLocale(config('app.locale'));

    expect($problems)->toBeEmpty(implode("\n", $problems));
});

it('لا توجد فئات CSS اتجاهية', function (): void {
    $pattern = '/\b(pl|pr|ml|mr|border-l|border-r|rounded-l|rounded-r|text-left|text-right)-(?!\[)/';
    $violations = [];

    $roots = [resource_path('views'), resource_path('css')];

    foreach (File::directories(src_path('Contexts')) as $context) {
        if (File::isDirectory($context.'/Presentation/Views')) {
            $roots[] = $context.'/Presentation/Views';
        }
    }

    foreach ($roots as $root) {
        if (! File::isDirectory($root)) {
            continue;
        }

        foreach (File::allFiles($root) as $file) {
            if (preg_match($pattern, $file->getContents(), $found)) {
                $violations[] = "{$file->getRelativePathname()}: {$found[0]}";
            }
        }
    }

    expect($violations)->toBeEmpty("فئات اتجاهية:\n".implode("\n", $violations));
});

it('لا توجد ألوان مكتوبة مباشرة خارج ملف الثيم', function (): void {
    $violations = [];
    $allowedPaths = [
        '/resources/css/',            // ملف الثيم — طبقة المنتج
        '/Infrastructure/Theming/',   // مولّد السُلَّم وألوان الحالة الثابتة
        '/database/',                 // بذور وميجريشنز
        '/config/',
        '/Tests/',                    // الاختبارات بتأكّد على قيم لون بعينها
    ];

    foreach (allPhpFiles([src_path(), app_path()]) as $file) {
        foreach ($allowedPaths as $allowed) {
            if (str_contains($file->getPathname(), $allowed)) {
                continue 2;
            }
        }

        if (preg_match('/#[0-9a-fA-F]{6}\b/', phpCodeWithoutComments($file->getContents()), $found)) {
            $violations[] = "{$file->getRelativePathname()}: {$found[0]}";
        }
    }

    expect($violations)->toBeEmpty("ألوان مباشرة:\n".implode("\n", $violations));
});

it('لا يوجد نص عربي مكتوب مباشرة في ملفات العرض', function (): void {
    $violations = [];
    $roots = [resource_path('views')];

    foreach (File::directories(src_path('Contexts')) as $context) {
        if (File::isDirectory($context.'/Presentation/Views')) {
            $roots[] = $context.'/Presentation/Views';
        }
    }

    foreach ($roots as $root) {
        foreach (File::allFiles($root) as $file) {
            // نص عربي بره {{ }} أو {!! !!} أو تعليق Blade
            $contents = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|\{\{--.*?--\}\}|@php.*?@endphp/s', '', $file->getContents());

            if (preg_match('/>\s*[\x{0600}-\x{06FF}]{3,}/u', (string) $contents, $found)) {
                $violations[] = "{$file->getRelativePathname()}: ".trim($found[0]);
            }
        }
    }

    expect($violations)->toBeEmpty("نصوص عربية مكتوبة مباشرة:\n".implode("\n", $violations));
});

it('يطبّق صيغ الجمع العربية الست', function (): void {
    app()->setLocale('ar');

    $renders = [];

    foreach ([0, 1, 2, 5, 11, 100] as $count) {
        $renders[$count] = trans_choice('common.results', $count, ['count' => fc_number($count)]);
    }

    expect($renders[0])->not->toContain('|')
        ->and($renders[1])->not->toContain('|')
        ->and($renders[2])->not->toContain('|')
        ->and($renders[5])->toContain('5')
        ->and($renders[11])->toContain('11')
        ->and($renders[100])->toContain('100');

    app()->setLocale(config('app.locale'));
});
