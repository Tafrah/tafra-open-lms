<?php

declare(strict_types=1);

namespace Src\Contexts\Installer;

use Src\Contexts\Installer\Infrastructure\Environment\EnvironmentFile;
use Src\Contexts\Installer\Infrastructure\InstallationState;
use Src\Contexts\Installer\Infrastructure\StorageLinker;
use Src\Contexts\Installer\Presentation\Console\InstallCommand;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

/**
 * المثبّت: `/install` للاستضافة المشتركة، و`php artisan lms:install` للـ VPS/Docker.
 *
 * مالوش شاشات Filament — صفحات Blade عادية بتشتغل قبل ما يكون فيه قاعدة
 * أصلاً. التحويل لـ `/install` في `bootstrap/app.php` (`RedirectToInstaller`
 * أول ميدلوير في `web`)، وتجهيز `.env` قبل التحميل في `PreInstallEnvironment`.
 */
final class InstallerServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'installer';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    /** @return list<string> */
    protected function panelIds(): array
    {
        return [];
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(InstallationState::class);

        $this->app->bind(EnvironmentFile::class, fn (): EnvironmentFile => new EnvironmentFile($this->app->environmentFilePath()));

        $this->app->bind(StorageLinker::class, static fn (): StorageLinker => new StorageLinker(
            public_path('storage'),
            storage_path('app/public'),
        ));
    }

    protected function bootContext(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}
