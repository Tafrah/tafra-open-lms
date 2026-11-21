<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Application\Queries;

use Carbon\CarbonInterface;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;

/** الواجهة العامة لسياق المحتوى (docs/01). */
final readonly class TenantContentSummary
{
    /** @return array{published: int, drafts: int} */
    public function forTenant(int $tenantId, CarbonInterface $since): array
    {
        return [
            'published' => Announcement::query()
                ->where('status', AnnouncementStatus::Published)
                ->where('published_at', '>=', $since)
                ->count(),
            'drafts' => Announcement::query()
                ->where('status', AnnouncementStatus::Draft)
                ->count(),
        ];
    }
}
