<?php

declare(strict_types=1);

namespace Src\Contexts\Media;

use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class MediaServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'media';
    }

    protected function path(): string
    {
        return __DIR__;
    }
}
