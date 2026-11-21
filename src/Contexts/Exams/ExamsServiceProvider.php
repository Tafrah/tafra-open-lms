<?php

declare(strict_types=1);

namespace Src\Contexts\Exams;

use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class ExamsServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'exams';
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
