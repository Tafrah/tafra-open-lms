<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Src\Contexts\Content\Domain\Events\AnnouncementPublished;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Infrastructure\Notifications\AnnouncementPublishedNotification;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;

/**
 * المستمع ده هو نقطة الوصل الوحيدة بين Content و Identity —
 * السياقات مابتستوردش موديلات بعض إلا عبر حدث (docs/01).
 */
final class NotifyUsersOfAnnouncement implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(AnnouncementPublished $event): void
    {
        $tenantId = $event->tenantId;

        if ($tenantId === null) {
            return;
        }

        app(TenantContext::class)->forTenant($tenantId, function () use ($event, $tenantId): void {
            $announcement = Announcement::query()->find($event->announcementId);

            if ($announcement === null) {
                return;
            }

            $notification = AnnouncementPublishedNotification::fromAnnouncement($announcement);

            User::query()
                ->ofTenant($tenantId)
                ->active()
                ->chunkById(200, static function (Collection $users) use ($notification): void {
                    Notification::send($users, $notification);
                });
        });
    }
}
