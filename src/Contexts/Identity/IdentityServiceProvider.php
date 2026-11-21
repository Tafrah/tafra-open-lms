<?php

declare(strict_types=1);

namespace Src\Contexts\Identity;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Src\Contexts\Identity\Domain\Events\DeviceRegistered;
use Src\Contexts\Identity\Domain\Events\UserInvited;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Repositories\UserRepository;
use Src\Contexts\Identity\Infrastructure\Listeners\NotifyOfNewDevice;
use Src\Contexts\Identity\Infrastructure\Listeners\RecordUserDevice;
use Src\Contexts\Identity\Infrastructure\Listeners\ReleaseUserDevice;
use Src\Contexts\Identity\Infrastructure\Listeners\SendUserInvitation;
use Src\Contexts\Identity\Infrastructure\Observers\UserPasswordObserver;
use Src\Contexts\Identity\Infrastructure\Repositories\EloquentUserRepository;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class IdentityServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'identity';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    protected function registerBindings(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
    }

    protected function bootContext(): void
    {
        Event::listen(Login::class, RecordUserDevice::class);
        Event::listen(Logout::class, ReleaseUserDevice::class);
        Event::listen(DeviceRegistered::class, NotifyOfNewDevice::class);
        Event::listen(UserInvited::class, SendUserInvitation::class);

        // تاريخ كلمات المرور + التنبيه الأمني في مكان واحد
        User::observe(UserPasswordObserver::class);
    }
}
