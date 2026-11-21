<?php

declare(strict_types=1);

namespace Src\Contexts\Content;

use Illuminate\Support\Facades\Event;
use Src\Contexts\Content\Domain\Events\AnnouncementPublished;
use Src\Contexts\Content\Infrastructure\Listeners\NotifyUsersOfAnnouncement;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class ContentServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'content';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    protected function bootContext(): void
    {
        Event::listen(AnnouncementPublished::class, NotifyUsersOfAnnouncement::class);
    }
}
