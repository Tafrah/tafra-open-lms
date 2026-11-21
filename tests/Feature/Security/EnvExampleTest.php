<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * القاعدة: أي مفتاح `.env` بنضيفه إحنا لازم يكون في `.env.example`
 * في نفس الـ commit (docs/00 بند ٤).
 *
 * الاختبار بيغطّي **ملفات الكونفيج بتاعتنا** بس — مفاتيح الباكدجات
 * الاختيارية (Pulse، Sentry، Reverb…) ليها قيم افتراضية معقولة،
 * ولو حطيناها كلها الملف يبقى ٢٠٠ سطر محدش بيقراهم.
 */
it('كل مفتاح env في كونفيجاتنا موجود في .env.example', function (): void {
    $ourConfigs = [
        'authorization.php', 'security.php', 'content.php', 'seeding.php',
        'notifications.php', 'logging.php', 'filesystems.php', 'tenancy.php',
    ];

    $example = File::get(base_path('.env.example'));
    $missing = [];

    foreach ($ourConfigs as $file) {
        $path = config_path($file);

        if (! File::exists($path)) {
            continue;
        }

        if (! preg_match_all('/env\(\s*[\'"]([A-Z0-9_]+)[\'"]/', File::get($path), $found)) {
            continue;
        }

        foreach (array_unique($found[1]) as $key) {
            if (! preg_match('/^'.preg_quote($key, '/').'=/m', $example)) {
                $missing[] = "{$file}: {$key}";
            }
        }
    }

    // كونفيج الباكدج الداخلي بيتعامل زي كونفيجاتنا
    $packageConfig = base_path('packages/future-code/filament-onboarding/config/fc-onboarding.php');

    if (File::exists($packageConfig) && preg_match_all('/env\(\s*[\'"]([A-Z0-9_]+)[\'"]/', File::get($packageConfig), $found)) {
        foreach (array_unique($found[1]) as $key) {
            if (! preg_match('/^'.preg_quote($key, '/').'=/m', $example)) {
                $missing[] = "fc-onboarding.php: {$key}";
            }
        }
    }

    expect($missing)->toBeEmpty("مفاتيح ناقصة في .env.example:\n".implode("\n", $missing));
});

it('لا يحتوي .env.example على قيم سرية حقيقية', function (): void {
    $example = File::get(base_path('.env.example'));

    expect($example)->not->toMatch('/^APP_KEY=base64:.+$/m')
        ->and($example)->not->toMatch('/^AWS_SECRET_ACCESS_KEY=.+$/m')
        ->and($example)->not->toMatch('/^SENTRY_LARAVEL_DSN=https:\/\/.+$/m');
});
