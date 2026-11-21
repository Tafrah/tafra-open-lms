<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Src\Contexts\Settings\Domain\Settings\GeneralSettings;
use Src\Contexts\Settings\Domain\Settings\MailSettings;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/**
 * بعض الإعدادات لازم تروّح لكونفيج Laravel (البريد، المنطقة الزمنية).
 *
 * الحارس `settingsAvailable()` إلزامي — من غيره أول `migrate`
 * على قاعدة فاضية بيكسر كل الأوامر (docs/05 بند ٦).
 */
final class DynamicConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->settingsAvailable()) {
            return;
        }

        try {
            $this->applyMailSettings();
            $this->applyGeneralSettings();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function applyMailSettings(): void
    {
        $mail = $this->app->make(MailSettings::class);

        config([
            'mail.default' => $mail->driver,
            'mail.mailers.smtp.host' => $mail->host,
            'mail.mailers.smtp.port' => $mail->port,
            'mail.mailers.smtp.username' => $mail->username ?: null,
            'mail.mailers.smtp.password' => $mail->password ?: null,
            'mail.mailers.smtp.encryption' => $mail->encryption,
            'mail.from.address' => $mail->from_address,
            'mail.from.name' => $mail->from_name[app()->getLocale()]
                ?? $mail->from_name[config('app.fallback_locale')]
                ?? config('app.name'),
        ]);
    }

    private function applyGeneralSettings(): void
    {
        $general = $this->app->make(GeneralSettings::class);

        $supported = (array) config('app.supported_locales', ['ar', 'en']);

        config(array_filter([
            'app.name' => $general->name(),
            'app.timezone' => $general->timezone,

            // اللغة الافتراضية من الإعدادات مش من .env — من غير كده
            // `User::preferredLocale()` بترجع للـ env، فمستخدم مالوش لغة
            // بياخد الإشعارات بلغة غير اللي المدير ظابطها (docs/09 بند ٤).
            'app.locale' => in_array($general->default_locale, $supported, true)
                ? $general->default_locale
                : null,
        ], static fn (mixed $value): bool => $value !== null));

        date_default_timezone_set($general->timezone);
    }

    private function settingsAvailable(): bool
    {
        // الاختبارات بتبني القاعدة من الصفر في كل حالة — الاستعلام هنا
        // بيتنفّذ مرة واحدة لكل عملية، فتكلفته مقبولة.
        try {
            return Schema::connection(TenancyMode::centralConnection())->hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
