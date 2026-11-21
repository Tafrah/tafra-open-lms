<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Application\Services;

use Src\Contexts\Notifications\Domain\Exceptions\UnknownNotificationKeyException;
use Src\Contexts\Notifications\Domain\Models\NotificationPreference;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Support\Application\Contracts\TenantContext;

/**
 * القنوات اللي إشعار معيّن هيروح عليها لمستخدم معيّن.
 *
 * القنوات الإجبارية (`required`) بتعدّي حتى لو المستخدم قافل الإشعار
 * — دي إشعارات أمنية أو قانونية (docs/09 بند ٣).
 */
final class NotificationChannelResolver
{
    /** @return list<string> */
    public function for(object $notifiable, string $key): array
    {
        $definition = config("notifications.catalog.{$key}");

        if (! is_array($definition)) {
            report(new UnknownNotificationKeyException($key));

            return ['database'];
        }

        $preference = NotificationPreference::query()
            ->where('user_id', $notifiable->getKey())
            ->where('notification_key', $key)
            ->where(function ($query): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', app(TenantContext::class)->id());
            })
            ->orderByRaw('tenant_id IS NULL')   // تفضيل المؤسسة يغلب العام
            ->first();

        if ($preference === null) {
            return $this->enabled($definition['default'] ?? []);
        }

        if (! $preference->enabled) {
            return $this->enabled($definition['required'] ?? []);
        }

        return $this->enabled(array_values(array_unique([
            ...array_intersect($preference->channels, $definition['channels'] ?? []),
            ...($definition['required'] ?? []),
        ])));
    }

    /**
     * القنوات المقفولة على مستوى النظام بتتشال — عشان بيئة من غير
     * بث أو بريد ما ترميش استثناءات في الطابور.
     *
     * @param list<string> $channels
     * @return list<string>
     */
    private function enabled(array $channels): array
    {
        $settings = $this->systemToggles();

        return array_values(array_filter(
            $channels,
            static fn (string $channel): bool => $settings[$channel] ?? true,
        ));
    }

    /**
     * مفاتيح القنوات: إعداد المؤسسة الحالية لو موجود، وإلا العام
     * (`TenantSettings` بيعمل الـ fallback — docs/05 بند ٤).
     *
     * @return array<string, bool>
     */
    private function systemToggles(): array
    {
        try {
            $settings = app(TenantSettings::class);

            return [
                'database' => (bool) $settings->get('notifications', 'database_enabled', true),
                'mail' => (bool) $settings->get('notifications', 'mail_enabled', true),
                'broadcast' => (bool) $settings->get('notifications', 'broadcast_enabled', false),
            ];
        } catch (\Throwable) {
            return ['database' => true, 'mail' => true, 'broadcast' => false];
        }
    }
}
