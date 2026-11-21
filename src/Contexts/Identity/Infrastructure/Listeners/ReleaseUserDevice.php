<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Listeners;

use Illuminate\Auth\Events\Logout;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserDevice;

/** بيفكّ ربط الجلسة بالجهاز عند الخروج — الجهاز بيفضل في القائمة كسجل. */
final class ReleaseUserDevice
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || ! request()->hasSession()) {
            return;
        }

        UserDevice::query()
            ->where('user_id', $user->getKey())
            ->where('session_id', request()->session()->getId())
            ->update(['session_id' => null]);
    }
}
