<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Src\Contexts\Identity\Domain\Events\DeviceRegistered;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Infrastructure\Notifications\NewDeviceLoginNotification;

final class NotifyOfNewDevice implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(DeviceRegistered $event): void
    {
        $user = User::query()->find($event->userId);

        $user?->notify(new NewDeviceLoginNotification($event->deviceName, $event->ipAddress));
    }
}
