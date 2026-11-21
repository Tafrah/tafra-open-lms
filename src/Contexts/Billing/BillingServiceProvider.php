<?php

declare(strict_types=1);

namespace Src\Contexts\Billing;

use Src\Contexts\Billing\Application\Queries\BootcampAccess;
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

    protected function registerBindings(): void
    {
        // ذاكرة طلب واحد — نفس سؤال «مشترك؟» بيتسأل كذا مرة في الصفحة
        $this->app->scoped(BootcampAccess::class);
    }
}
