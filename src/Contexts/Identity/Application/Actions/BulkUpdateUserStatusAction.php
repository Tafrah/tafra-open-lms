<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;

final readonly class BulkUpdateUserStatusAction
{
    /** @param  Collection<int, User>  $users */
    public function handle(Collection $users, UserStatus $status): int
    {
        return DB::transaction(function () use ($users, $status): int {
            $count = 0;

            foreach ($users as $user) {
                $user->forceFill([
                    'status' => $status,
                    'is_active' => $status === UserStatus::Active,
                ])->save();

                $count++;
            }

            activity('security')
                ->withProperties(['count' => $count, 'status' => $status->value])
                ->log('bulk_user_status_changed');

            return $count;
        });
    }
}
