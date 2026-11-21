<?php

declare(strict_types=1);

namespace Src\Support\Application\Actions\Records;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\DataObjects\RecordQuery;

/**
 * صفحة سجلات لأي مورد — نفس اللي جدول Filament بيعرضه.
 *
 * الاستعلام الأساسي جاي جاهز من المورد (نطاق المؤسسة والعلاقات)، والـ
 * Action بيضيف المعايير من قائمة بيضاء ويسأل `viewAny`. كل صف بيعدّي على
 * `view` كمان — دفاع زيادة لو نطاق اتنسى.
 */
final readonly class ListRecordsAction
{
    /**
     * @param Builder<Model> $query
     * @return CursorPaginator<int, Model>
     */
    public function handle(User $actor, Builder $query, RecordQuery $criteria): CursorPaginator
    {
        Gate::forUser($actor)->authorize('viewAny', $query->getModel()::class);

        $this->applySearch($query, $criteria);

        foreach ($criteria->filters as $column => $value) {
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        if ($criteria->trashed === 'with' && method_exists($query, 'withTrashed')) {
            $query->withTrashed();
        } elseif ($criteria->trashed === 'only' && method_exists($query, 'onlyTrashed')) {
            $query->onlyTrashed();
        }

        // ترتيب ثابت: العمود المطلوب وبعده المفتاح — شرط الـ cursor
        $key = $query->getModel()->getKeyName();
        $direction = $criteria->sortDirection === 'asc' ? 'asc' : 'desc';

        $query->reorder();

        if ($criteria->sortColumn !== null) {
            $query->orderBy($criteria->sortColumn, $direction);
        }

        $query->orderBy($key, $direction);

        $page = $query->cursorPaginate($criteria->limit, ['*'], 'cursor', $criteria->cursor);

        $page->setCollection($page->getCollection()->filter(
            static fn (Model $record): bool => Gate::forUser($actor)->allows('view', $record),
        )->values());

        return $page;
    }

    /** @param Builder<covariant Model> $query */
    private function applySearch(Builder $query, RecordQuery $criteria): void
    {
        $term = trim((string) $criteria->search);

        if ($term === '' || $criteria->searchColumns === []) {
            return;
        }

        // `%` و`_` في البحث حروف عادية — مش wildcard
        $pattern = '%'.Str::of($term)->replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_']).'%';
        $locales = (array) config('app.supported_locales');

        $query->where(function (Builder $group) use ($criteria, $pattern, $locales): void {
            foreach ($criteria->searchColumns as $column) {
                if (in_array($column, $criteria->translatableColumns, true)) {
                    foreach ($locales as $locale) {
                        $group->orWhere("{$column}->{$locale}", 'ilike', $pattern);
                    }

                    continue;
                }

                $group->orWhere($column, 'ilike', $pattern);
            }
        });
    }
}
