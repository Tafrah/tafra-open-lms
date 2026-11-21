<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;

/**
 * الكورسات المنشورة اللي بتظهر في الموقع — بالصف والصورة محمّلين مسبقًا
 * (مفيش N+1 في كروت الكورسات).
 */
final readonly class CourseCatalog
{
    /** @return Builder<Bootcamp> */
    public function query(): Builder
    {
        return Bootcamp::query()
            ->published()
            ->with(['level', 'media'])
            ->withCount([
                'sections as sections_count',
            ]);
    }

    /** @return Collection<int, Bootcamp> */
    public function featured(int $limit = 6): Collection
    {
        return $this->query()
            ->where('is_featured', true)
            ->orderByRaw('featured_order is null')
            ->orderBy('featured_order')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Bootcamp> */
    public function latest(int $limit = 9): Collection
    {
        return $this->query()->latest('id')->limit($limit)->get();
    }

    /** @return LengthAwarePaginator<int, Bootcamp> */
    public function search(?int $levelId, ?string $term, int $perPage): LengthAwarePaginator
    {
        $term = $term !== null ? trim(mb_substr($term, 0, 100)) : null;

        return $this->query()
            ->when($levelId !== null, fn (Builder $q) => $q->where('educational_level_id', $levelId))
            ->when(filled($term), function (Builder $q) use ($term): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], (string) $term).'%';
                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('short_description', 'like', $like));
            })
            ->orderByDesc('is_featured')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, EducationalLevel> */
    public function levels(): Collection
    {
        return EducationalLevel::query()
            ->withCount(['bootcamps' => fn (Builder $q) => $q->where('publish_status', PublishStatus::Published)])
            ->orderBy('order')
            ->get();
    }

    public function findPublished(string $slug): ?Bootcamp
    {
        return Bootcamp::query()->published()->with(['level', 'media'])->where('slug', $slug)->first();
    }
}
