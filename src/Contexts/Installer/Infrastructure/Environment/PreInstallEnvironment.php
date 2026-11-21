<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Infrastructure\Environment;

/**
 * أول طلب على نسخة مرفوعة على استضافة مشتركة: مفيش `.env` ولا `APP_KEY`.
 *
 * من غير مفتاح، Laravel بيرمي `MissingAppKeyException` أول ما الكوكيز
 * تتشفّر — يعني صفحة المثبّت نفسها كانت هتطلع ٥٠٠. فقبل ما المتغيّرات
 * تتحمّل (`bootstrap/app.php` ← `beforeBootstrapping`) بنكتب `.env` مؤقت
 * بمفتاح جديد وجلسات على ملفات، والمثبّت بيستبدله في آخر خطوة.
 *
 * لو المجلد مش قابل للكتابة: القيم في الذاكرة بس — صفحة المتطلبات بتظهر
 * وبتقول للمستخدم يصلّح الصلاحيات.
 *
 * مابيعملش حاجة في: سطر الأوامر (`lms:install` بيكتب الملف بنفسه)، ولا
 * لو `APP_KEY` جاي من البيئة (Docker / Coolify)، ولا لو الملف موجود.
 */
final class PreInstallEnvironment
{
    public static function prepare(string $environmentFile): void
    {
        if (is_file($environmentFile) || PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' || self::keyFromEnvironment()) {
            return;
        }

        $sections = EnvironmentBlueprint::preInstall(EnvironmentBlueprint::generateKey());

        try {
            (new EnvironmentFile($environmentFile))->write($sections);

            return;
        } catch (\Throwable) {
            // مش قابل للكتابة — نكمّل بالذاكرة
        }

        foreach ($sections as $values) {
            foreach ($values as $key => $value) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    private static function keyFromEnvironment(): bool
    {
        $key = getenv('APP_KEY');

        return (is_string($key) && $key !== '') || ! empty($_ENV['APP_KEY']) || ! empty($_SERVER['APP_KEY']);
    }
}
