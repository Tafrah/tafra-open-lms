<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Src\Contexts\Content\Domain\Models\Announcement;

/**
 * إعلانات المدرس المنشورة (ومش منتهية) للطلبة — الأحدث الأول.
 * نفس إعلانات اللوحة؛ مستخدمو اللوحة لسه بياخدوا إشعارهم زي ما هو.
 */
final class StudentAnnouncements
{
    /** @return Collection<int, Announcement> */
    public function latest(int $limit = 10): Collection
    {
        return Announcement::query()
            ->active()
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->limit($limit)
            ->get(['id', 'tenant_id', 'title', 'body', 'severity', 'is_pinned', 'published_at']);
    }
}
