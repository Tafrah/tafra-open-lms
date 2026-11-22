<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * الوثائق هنا مش زينة — الكود بيحيل عليها بالبند («راجع docs/21 بند ١٦»)،
 * والبنود دي هي الشرح الوحيد لقرارات مش واضحة من الكود نفسه.
 *
 * مرجع بايظ أسوأ من مفيش مرجع: بيوّدي القارئ لبند تاني خالص وهو واثق
 * إنه في المكان الصح. حصل فعلاً مرتين — `AppServiceProvider` كان بيحيل
 * على بند ١٤ بدل ١٦، و`MigrationsTest` على بند ٢٠ بدل ٢١ — والاتنين
 * عدّوا كل البوابات لأن مفيش حاجة بتفحص المراجع.
 */

/** @return list<string> */
function documentedFiles(): array
{
    $files = [];

    $roots = ['app', 'src', 'tests', 'database', 'config', 'routes', 'docs', 'packages'];

    foreach ($roots as $root) {
        if (! File::isDirectory(base_path($root))) {
            continue;
        }

        foreach (File::allFiles(base_path($root)) as $file) {
            if (in_array($file->getExtension(), ['php', 'md', 'sh'], true)) {
                $files[] = $file->getPathname();
            }
        }
    }

    foreach (['README.md', 'CLAUDE.md', 'PRODUCTION-READINESS.md', 'deploy.sh', 'rollback.sh'] as $file) {
        if (File::exists(base_path($file))) {
            $files[] = base_path($file);
        }
    }

    return $files;
}

/**
 * أرقام البنود في الوثائق مكتوبة بالأرقام العربية الهندية (`## ١٦.`).
 *
 * @return list<string> أرقام البنود الموجودة فعلاً في الملف
 */
function sectionNumbersIn(string $path): array
{
    preg_match_all('/^## ([٠-٩]+)/mu', (string) File::get($path), $matches);

    return array_values(array_unique($matches[1]));
}

/** بيحوّل `21` أو `docs/21-implementation-notes.md` لمسار الملف الحقيقي. */
function resolveDocPath(string $reference): ?string
{
    if (str_starts_with($reference, 'README')) {
        return File::exists(base_path('README.md')) ? base_path('README.md') : null;
    }

    $number = (string) preg_replace('/\D/', '', explode('-', $reference)[0]);

    $matches = File::glob(base_path("docs/{$number}-*.md"));

    return $matches[0] ?? null;
}

it('كل إحالة لبند في الوثائق بتوصل لبند موجود فعلاً', function (): void {
    $problems = [];
    $checked = 0;

    // `docs/21 بند ١٦` · `` `docs/21` بند ١٦ `` · `docs/21-implementation-notes.md بند ٨`
    $pattern = '/`?(docs\/\d{2}[\w\-.]*|README(?:\.md)?)`?\s+بند\s+([٠-٩]+)/u';

    foreach (documentedFiles() as $path) {
        $contents = (string) File::get($path);

        if (! preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
            continue;
        }

        $relative = str_replace(base_path().'/', '', $path);

        foreach ($matches as [, $document, $section]) {
            $checked++;

            $target = resolveDocPath($document);

            if ($target === null) {
                $problems[] = "{$relative}: بيحيل على «{$document}» — الملف ده مش موجود";

                continue;
            }

            $available = sectionNumbersIn($target);

            if (! in_array($section, $available, true)) {
                $name = basename($target);
                $problems[] = "{$relative}: «{$document} بند {$section}» — مفيش بند {$section} في {$name} (الموجود: ".implode('، ', $available).')';
            }
        }
    }

    expect($checked)->toBeGreaterThan(100, 'الفحص مالقاش الإحالات — يبقى النمط اتكسر');
    expect($problems)->toBeEmpty("إحالات بايظة:\n".implode("\n", $problems));
});

it('ترقيم البنود في كل وثيقة متصل من غير فجوات', function (): void {
    $problems = [];

    foreach (File::glob(base_path('docs/*.md')) as $path) {
        $numbers = array_map(
            static fn (string $arabic): int => (int) strtr($arabic, array_combine(
                ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
                ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            )),
            sectionNumbersIn($path),
        );

        if ($numbers === []) {
            continue;
        }

        sort($numbers);
        $expected = range(1, max($numbers));
        $missing = array_diff($expected, $numbers);

        if ($missing !== []) {
            $problems[] = basename($path).': ناقص بند '.implode('، ', $missing);
        }
    }

    expect($problems)->toBeEmpty("فجوات في ترقيم البنود:\n".implode("\n", $problems));
});
