<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Src\Contexts\Identity\Domain\Events\UserInvited;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Infrastructure\Notifications\UserInvitedNotification;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

final class SendUserInvitation implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(UserInvited $event): void
    {
        $user = User::query()->find($event->userId);

        if ($user === null || $event->inviteUrl === null) {
            return;
        }

        $tenant = Tenant::query()->find($event->tenantId);

        $organization = $tenant === null
            ? (string) config('app.name')
            : (string) $tenant->name;

        $user->notify(new UserInvitedNotification($event->inviteUrl, $organization));
    }
}
