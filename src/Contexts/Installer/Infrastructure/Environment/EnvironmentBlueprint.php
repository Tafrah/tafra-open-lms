<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Infrastructure\Environment;

/**
 * محتوى `.env` للاستضافة المشتركة: مفيش Redis، مفيش عامل طابور، مفيش Reverb.
 *
 * كل حاجة على القاعدة (كاش، جلسات، طوابير)، والطابور بيشتغل من الجدولة
 * (`routes/console.php`). البريد `log` لحد ما المدير يضبطه من الإعدادات.
 *
 * ⚠️ مفيش `config()` ولا `__()` هنا — `preInstall()` بيتنادى قبل ما
 * Laravel يحمّل أي حاجة (`PreInstallEnvironment`).
 */
final class EnvironmentBlueprint
{
    public static function generateKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /**
     * أول طلب على نسخة مرفوعة لسه: مفيش قاعدة — الجلسات والكاش على ملفات
     * عشان صفحات المثبّت تشتغل (CSRF محتاج جلسة).
     *
     * @return array<string, array<string, string>>
     */
    public static function preInstall(string $key): array
    {
        return [
            'Tafra Open LMS — pre-install (/install rewrites this file)' => [
                ...self::application($key, 'Tafra LMS', 'http://localhost'),
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_DATABASE' => '',
                'DB_USERNAME' => '',
                'DB_PASSWORD' => '',
                'TENANCY_MODE' => 'single',
                'CACHE_STORE' => 'file',
                'SESSION_DRIVER' => 'file',
                'SESSION_ENCRYPT' => 'true',
                'QUEUE_CONNECTION' => 'sync',
                'BROADCAST_CONNECTION' => 'log',
                'MAIL_MAILER' => 'log',
                'PULSE_ENABLED' => 'false',
                'TELESCOPE_ENABLED' => 'false',
                'PERMISSION_CACHE_STORE' => 'file',
                'SETTINGS_CACHE_ENABLED' => 'false',
                'SETTINGS_CACHE_STORE' => 'file',
                'LOG_CHANNEL' => 'daily',
                'LOG_LEVEL' => 'error',
            ],
        ];
    }

    /**
     * @param array<string, string> $database مفاتيح `DB_*`
     * @return array<string, array<string, string>>
     */
    public static function installed(
        string $key,
        string $name,
        string $url,
        array $database,
        string $mailFrom,
        bool $secureCookies,
        ?string $publicStorageUrl,
        string $version,
    ): array {
        return [
            'Application' => [
                ...self::application($key, $name, $url),
                'APP_VERSION' => $version,
                'TRUSTED_PROXIES' => '*',
            ],
            'Database' => $database,
            'Tenancy — locked on install (php artisan tenancy:init)' => [
                'TENANCY_MODE' => 'single',
            ],
            'Shared hosting drivers — no Redis, no workers (queue runs from the scheduler cron)' => [
                'CACHE_STORE' => 'database',
                'CACHE_PREFIX' => 'tafra_lms',
                'QUEUE_CONNECTION' => 'database',
                'QUEUE_WORKER_VIA_SCHEDULER' => 'true',
                'SESSION_DRIVER' => 'database',
                'SESSION_LIFETIME' => '120',
                'SESSION_ENCRYPT' => 'true',
                'SESSION_SECURE_COOKIE' => $secureCookies ? 'true' : 'false',
                'BROADCAST_CONNECTION' => 'log',
                'PERMISSION_CACHE_STORE' => 'database',
                'SETTINGS_CACHE_ENABLED' => 'true',
                'SETTINGS_CACHE_STORE' => 'database',
                'PULSE_ENABLED' => 'false',
                'TELESCOPE_ENABLED' => 'false',
            ],
            'Files' => array_filter([
                'FILESYSTEM_DISK' => 'public',
                'MEDIA_DISK' => 'public',
                'PUBLIC_STORAGE_URL' => $publicStorageUrl,
                'BACKUP_DISKS' => 'backups-local',
            ], static fn (?string $value): bool => $value !== null),
            'Mail — configure SMTP from the admin panel settings' => [
                'MAIL_MAILER' => 'log',
                'MAIL_FROM_ADDRESS' => $mailFrom,
                'MAIL_FROM_NAME' => $name,
            ],
            'Logs' => [
                'LOG_CHANNEL' => 'stack',
                'LOG_STACK' => 'daily',
                'LOG_LEVEL' => 'error',
                'LOG_DAILY_DAYS' => '14',
            ],
            'Security' => [
                'CSP_ENABLED' => 'true',
                'CSP_ENFORCE' => 'false',
            ],
        ];
    }

    /** @return array<string, string> */
    private static function application(string $key, string $name, string $url): array
    {
        return [
            'APP_NAME' => $name,
            'APP_ENV' => 'production',
            'APP_KEY' => $key,
            'APP_DEBUG' => 'false',
            'APP_URL' => $url,
            'APP_LOCALE' => 'ar',
            'APP_FALLBACK_LOCALE' => 'en',
            'APP_TIMEZONE' => 'Africa/Cairo',
        ];
    }
}
