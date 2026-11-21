<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/** @return list<SplFileInfo> */
function allMigrationFiles(): array
{
    $files = [];

    $paths = [
        base_path('database/migrations'),
        ...File::glob(base_path('src/Contexts/*/Database/Migrations')),
        ...File::glob(base_path('src/Contexts/*/Database/Migrations/Tenant')),
        base_path('packages/future-code/filament-onboarding/database/migrations'),
    ];

    foreach ($paths as $path) {
        if (! File::isDirectory($path)) {
            continue;
        }

        foreach (File::files($path) as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file;
            }
        }
    }

    return $files;
}

/**
 * أي ميجريشن من غير `down()` بيكسر `migrate:rollback` — ولو الجدول فضل
 * موجود، أول `migrate` بعدها بيفشل بـ «relation already exists».
 * حصل فعلاً مع ٤ ميجريشنز منشورة من باكدجات (docs/21 بند ٢١).
 */
it('كل ميجريشن بيقدر يترجع', function (): void {
    $problems = [];

    // الاستثناءات دي مقصودة وموثّقة في الملف نفسه
    $intentional = ['enable_postgres_search_extensions'];

    foreach (allMigrationFiles() as $file) {
        foreach ($intentional as $skip) {
            if (str_contains($file->getFilename(), $skip)) {
                continue 2;
            }
        }

        $contents = $file->getContents();

        if (! str_contains($contents, 'function down')) {
            $problems[] = "{$file->getFilename()}: مفيش down()";

            continue;
        }

        $down = (string) preg_replace('/^.*function down\(\)/s', '', $contents);

        if (! preg_match('/\b(drop|DROP)/', $down)) {
            $problems[] = "{$file->getFilename()}: down() مابيرجّعش حاجة";
        }
    }

    expect($problems)->toBeEmpty("ميجريشنز مش قابلة للرجوع:\n".implode("\n", $problems));
});

it('كل جدول تابع لمؤسسة عليه فهرس مركّب يبدأ بـ tenant_id', function (): void {
    $problems = [];

    foreach (allMigrationFiles() as $file) {
        $contents = $file->getContents();

        if (! str_contains($contents, "foreignId('tenant_id')")) {
            continue;
        }

        // الجداول اللي `tenant_id` فيها nullable ومش مملوكة لمؤسسة
        if (preg_match("/foreignId\('tenant_id'\)->nullable\(\)/", $contents)) {
            continue;
        }

        // `unique()` و`primary()` بيعملوا فهرس زيهم زي `index()` —
        // المهم إن `tenant_id` يكون العمود الأول فيه
        if (! preg_match("/(index|unique|primary)\(\[\s*'tenant_id'/", $contents)) {
            $problems[] = $file->getFilename();
        }
    }

    expect($problems)->toBeEmpty(
        "جداول فيها tenant_id من غير فهرس مركّب يبدأ بيه:\n".implode("\n", $problems),
    );
});

/**
 * في وضع multi، جداول المستأجرين في قاعدة والجداول المركزية في قاعدة تانية —
 * Postgres مابيقبلش foreign key بين قاعدتين، والميجريشن بتفشل وقت تجهيز
 * أول مؤسسة بس (docs/03 بند ٣). `TenantBlueprint` بيعمل FK في single بس.
 */
it('ميجريشنز المستأجرين مابتربطش جداول مركزية بـ constrained()', function (): void {
    $problems = [];

    foreach (File::glob(base_path('src/Contexts/*/Database/Migrations/Tenant/*.php')) as $path) {
        $contents = (string) File::get($path);

        if (preg_match("/foreignId\('(tenant_id|[a-z_]+_id)'\)[^;]*->constrained\(/", $contents)
            || preg_match('/->foreign\(/', $contents)) {
            $problems[] = basename($path);
        }
    }

    expect($problems)->toBeEmpty(
        "ميجريشنز مستأجرين فيها FK مباشر — استخدم TenantBlueprint:\n".implode("\n", $problems),
    );
});
