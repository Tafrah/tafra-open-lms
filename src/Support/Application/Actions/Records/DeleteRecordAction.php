<?php

declare(strict_types=1);

namespace Src\Support\Application\Actions\Records;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;

/**
 * حذف واسترجاع وحذف نهائي لسجل واحد — كل واحد بقدرته في الـ Policy،
 * وقواعد السلامة (آخر مدير، إعلان منشور…) بتسري زي اللوحة بالظبط.
 */
final readonly class DeleteRecordAction
{
    /** حذف (لسلة المحذوفات لو الموديل بيدعمها) */
    public function remove(User $actor, Model $record): Model
    {
        Gate::forUser($actor)->authorize('delete', $record);

        return $this->inTransaction($record, static function (Model $record): void {
            $record->delete();
        });
    }

    public function restore(User $actor, Model $record): Model
    {
        Gate::forUser($actor)->authorize('restore', $record);

        return $this->inTransaction($record, static function (Model $record): void {
            if (method_exists($record, 'restore')) {
                $record->restore();
            }
        });
    }

    /** حذف نهائي من سلة المحذوفات */
    public function purge(User $actor, Model $record): Model
    {
        Gate::forUser($actor)->authorize('forceDelete', $record);

        return $this->inTransaction($record, static function (Model $record): void {
            $record->forceDelete();
        });
    }

    /** @param callable(Model): void $operation */
    private function inTransaction(Model $record, callable $operation): Model
    {
        DB::connection($record->getConnectionName())->transaction(static fn () => $operation($record));

        return $record;
    }
}
