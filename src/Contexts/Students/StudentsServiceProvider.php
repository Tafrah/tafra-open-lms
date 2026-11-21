<?php

declare(strict_types=1);

namespace Src\Contexts\Students;

use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class StudentsServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'students';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    /** @return list<string> */
    protected function panelIds(): array
    {
        return ['admin'];
    }
}
