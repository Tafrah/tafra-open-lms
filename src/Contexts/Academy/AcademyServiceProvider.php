<?php

declare(strict_types=1);

namespace Src\Contexts\Academy;

use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class AcademyServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'academy';
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
