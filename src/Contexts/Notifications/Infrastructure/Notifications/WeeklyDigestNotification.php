<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Infrastructure\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * الملخص الأسبوعي.
 *
 * افتراضياً **مقفول** في الكتالوج (`default => []`) — المستخدم لازم
 * يفعّله بنفسه. بريد بس، مفيش إشعار لوحة (docs/09).
 */
final class WeeklyDigestNotification extends BaseNotification
{
    /** @param  array<string, int>  $stats */
    public function __construct(
        public readonly string $organization,
        public readonly array $stats,
    ) {
        parent::__construct();
    }

    public static function key(): string
    {
        return 'weekly_digest';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('notifications::notifications.digest.subject', ['organization' => $this->organization]))
            ->greeting(__('common.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications::notifications.digest.intro'));

        foreach ($this->stats as $key => $value) {
            $message->line('· '.__("notifications::notifications.digest.stats.{$key}", ['count' => fc_number($value)]));
        }

        return $message->salutation(__('common.salutation'));
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('notifications::notifications.digest.subject', ['organization' => $this->organization]),
            'stats' => $this->stats,
        ];
    }
}
