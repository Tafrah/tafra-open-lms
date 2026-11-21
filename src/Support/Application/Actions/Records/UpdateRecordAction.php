<?php

declare(strict_types=1);

namespace Src\Support\Application\Actions\Records;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * تعديل سجل — `update` على السجل، وكل حقل حساس بقدرته هو كمان.
 *
 * `$fieldAbilities` = نفس `->saved(fn () => can('pin', $record))` في الفورم:
 * الحقل اللي المستخدم مايقدرش عليه **بيترفض** — مش بيتجاهل بصمت، عشان
 * النموذج مايفتكرش إنه اتحفظ (docs/21 بند ٩).
 */
final readonly class UpdateRecordAction
{
    /**
     * @template TModel of Model
     *
     * @param TModel $record
     * @param array<string, mixed> $attributes
     * @param array<string, string> $fieldAbilities [حقل => قدرة]
     * @return TModel
     */
    public function handle(User $actor, Model $record, array $attributes, array $fieldAbilities = []): Model
    {
        $gate = Gate::forUser($actor);

        $gate->authorize('update', $record);

        foreach ($fieldAbilities as $field => $ability) {
            if (array_key_exists($field, $attributes)) {
                $gate->authorize($ability, $record);
            }
        }

        return DB::connection($record->getConnectionName())->transaction(static function () use ($record, $attributes): Model {
            $record->fill($attributes)->save();

            return $record->refresh();
        });
    }
}
