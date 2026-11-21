<?php

declare(strict_types=1);

namespace Src\Support\Application\Actions\Records;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * إنشاء سجل — نفس اللي `CreateRecord` في Filament بيعمله، بس كـ Action.
 *
 * الحقول جاية من قائمة بيضاء في الإعلان، و`fill()` بيحترم `$fillable` فوقها —
 * `tenant_id` مش مدخل أبداً (`BelongsToTenant` هو اللي بيحطه).
 */
final readonly class CreateRecordAction
{
    /**
     * @template TModel of Model
     *
     * @param class-string<TModel> $modelClass
     * @param array<string, mixed> $attributes
     * @return TModel
     */
    public function handle(User $actor, string $modelClass, array $attributes): Model
    {
        Gate::forUser($actor)->authorize('create', $modelClass);

        /** @var TModel $record */
        $record = new $modelClass;

        return DB::connection($record->getConnectionName())->transaction(static function () use ($record, $attributes): Model {
            $record->fill($attributes)->save();

            return $record->refresh();
        });
    }
}
