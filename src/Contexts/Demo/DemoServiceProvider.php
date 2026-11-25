<?php

declare(strict_types=1);

namespace Src\Contexts\Demo;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\View;
use Src\Contexts\Demo\Application\DemoMode;
use Src\Contexts\Demo\Presentation\Console\DemoCommand;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;

/**
 * نسخة التجربة: `lms:demo` (تثبيت + بيانات)، أزرار «ادخل كـ…» في صفحتي الدخول،
 * وشارة «نسخة تجربة» في اللوحة والموقع. كل ده بيظهر بس لو `DemoMode::active()`.
 */
final class DemoServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'demo';
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
        $this->app->singleton(DemoMode::class);
    }

    protected function bootContext(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([DemoCommand::class]);
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            static fn (): string => app(DemoMode::class)->active()
                ? view('demo::admin-login', [
                    'accounts' => app(DemoMode::class)->accounts('web'),
                    'password' => app(DemoMode::class)->password(),
                ])->render()
                : '',
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            static fn (): string => app(DemoMode::class)->active()
                ? view('demo::admin-chip', ['resetAt' => self::resetAt()])->render()
                : '',
        );

        // الموقع مالوش render hooks — الصفحات بتسأل عن المتغيّرات دي (`@isset($demo)`)
        View::composer(['storefront::pages.auth.login', 'storefront::layouts.base'], static function ($view): void {
            $demo = app(DemoMode::class);

            if (! $demo->active()) {
                return;
            }

            $view->with('demo', [
                'phone' => (string) ($demo->account('student')['phone'] ?? ''),
                'password' => $demo->password(),
                'resetAt' => self::resetAt(),
            ]);
        });
    }

    private static function resetAt(): ?string
    {
        return config('demo.auto_reset') ? (string) config('demo.reset_at') : null;
    }
}
