<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Src\Contexts\Notifications\Application\Services\NotificationChannelResolver;

/**
 * الأساس لكل إشعار.
 *
 * ١. كل إشعار في الطابور — مفيش إشعار متزامن.
 * ٢. `afterCommit()` في الكونستركتور — بيمنع سباق شهير: الطابور بياخد
 *    الـ Job قبل ما الترانزاكشن تُحفظ فالسجل مش موجود.
 * ٣. `key()` بيربط الإشعار بكتالوج التفضيلات.
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct()
    {
        $this->afterCommit();
        $this->onQueue((string) config('notifications.queue', 'notifications'));
    }

    /** مفتاح الإشعار في `config/notifications.php` */
    abstract public static function key(): string;

    /** @return array<string, mixed> */
    abstract public function toDatabase(object $notifiable): array;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return app(NotificationChannelResolver::class)->for($notifiable, static::key());
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
