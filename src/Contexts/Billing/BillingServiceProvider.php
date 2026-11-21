<?php

declare(strict_types=1);

namespace Src\Contexts\Billing;

use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class BillingServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'billing';
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
