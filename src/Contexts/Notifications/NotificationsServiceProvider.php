<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications;

use Src\Contexts\Notifications\Application\Services\NotificationChannelResolver;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class NotificationsServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'notifications';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(NotificationChannelResolver::class);
    }
}
