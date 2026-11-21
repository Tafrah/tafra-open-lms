<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Src\Contexts\Content\Application\Actions\PublishAnnouncementAction;
use Src\Contexts\Content\Application\DataObjects\PublishAnnouncementData;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/**
 * بينشر الإعلانات اللي اتحدد ليها `published_at` في الماضي وهي لسه مسودة.
 *
 * الاستدعاء نظامي (`$actor = null`) — صريح إنه بيعدّي بدون فاعل،
 * مش بالسهو (docs/19 بند ١٣).
 */
final class PublishScheduledAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:publish-scheduled';

    protected $description = 'نشر الإعلانات اللي حان موعد نشرها';

    public function handle(TenantContext $context, PublishAnnouncementAction $action): int
    {
        $published = 0;

        $context->forEachTenant(function (Tenant $tenant) use ($action, &$published): void {
            Announcement::query()
                ->where('status', AnnouncementStatus::Draft)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->cursor()
                ->each(function (Announcement $announcement) use ($action, &$published): void {
                    $action->handle(new PublishAnnouncementData(
                        announcementId: (int) $announcement->getKey(),
                        publishAt: $announcement->published_at?->toImmutable(),
                    ));

                    $published++;
                });
        });

        $this->info("اتنشر {$published} إعلان مجدول.");

        return self::SUCCESS;
    }
}
