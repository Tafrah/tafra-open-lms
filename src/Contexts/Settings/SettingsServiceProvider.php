<?php

declare(strict_types=1);

namespace Src\Contexts\Settings;

use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

final class SettingsServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'settings';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(TenantSettings::class);
    }
}
