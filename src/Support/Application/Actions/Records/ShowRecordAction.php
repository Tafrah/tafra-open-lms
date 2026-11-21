<?php

declare(strict_types=1);

namespace Src\Support\Application\Actions\Records;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * سجل واحد بالمفتاح — من نفس استعلام المورد، فسجل مؤسسة تانية «مش موجود»
 * أصلاً، و`view` بيتسأل بعدها (404 مش 403 لو الـ Policy قررت كده).
 */
final readonly class ShowRecordAction
{
    /**
     * @template TModel of Model
     *
     * @param Builder<TModel> $query
     * @return TModel
     */
    public function handle(User $actor, Builder $query, int|string $id, string $ability = 'view', bool $withTrashed = false): Model
    {
        if ($withTrashed && method_exists($query, 'withTrashed')) {
            $query->withTrashed();
        }

        $record = $query->whereKey($id)->firstOrFail();

        Gate::forUser($actor)->authorize($ability, $record);

        return $record;
    }
}
