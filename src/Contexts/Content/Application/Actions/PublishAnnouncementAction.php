<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Application\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Content\Application\DataObjects\PublishAnnouncementData;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Events\AnnouncementPublished;
use Src\Contexts\Content\Domain\Exceptions\AnnouncementAlreadyPublishedException;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Identity\Domain\Models\User;

final readonly class PublishAnnouncementAction
{
    public function __construct(
        private Dispatcher $events,
    ) {}

    public function handle(PublishAnnouncementData $data, ?User $actor = null): Announcement
    {
        return DB::transaction(function () use ($data, $actor): Announcement {
            $announcement = Announcement::query()->findOrFail($data->announcementId);

            // الحاجز الأخير: الـ Action ممكن يتنادى من API أو Job أو Command.
            // $actor = null معناه استدعاء نظامي صريح (أمر مجدول) — مش سهو.
            if ($actor !== null) {
                Gate::forUser($actor)->authorize('publish', $announcement);
            }

            throw_if(
                $announcement->status === AnnouncementStatus::Published,
                new AnnouncementAlreadyPublishedException((int) $announcement->getKey()),
            );

            $announcement->update([
                'status' => AnnouncementStatus::Published,
                'published_at' => $data->publishAt ?? now(),
            ]);

            if ($data->notifyUsers) {
                $this->events->dispatch(new AnnouncementPublished(
                    (int) $announcement->getKey(),
                    (int) $announcement->tenant_id,
                ));
            }

            return $announcement;
        });
    }
}
