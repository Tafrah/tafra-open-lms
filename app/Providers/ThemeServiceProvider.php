<?php

declare(strict_types=1);

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;
use Src\Support\Presentation\Livewire\LocaleSwitcher;

/**
 * كل ما بيلمس شكل اللوحة من بره الـ CSS: متغيّرات لون العلامة،
 * الفوتر، شريط الانتحال، تنبيه الصيانة (docs/06 و docs/07 بند ٩).
 */
final class ThemeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('fc.locale-switcher', LocaleSwitcher::class);

        $this->registerBrandVariables();
        $this->registerTourTranslations();
        $this->registerFooter();
        $this->registerBanners();
    }

    /**
     * لون العلامة بيتغيّر لكل مؤسسة — فبيتحقن كمتغيّرات CSS في الـ head.
     * الناتج مكاش بمفتاح فيه اللون نفسه، فالتغيير بيبطّله تلقائياً.
     */
    private function registerBrandVariables(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            static function (): string {
                $resolver = app(ThemeColorResolver::class);
                $hex = $resolver->primaryHex();

                return Cache::remember(
                    'theme:brand-variables:'.md5($hex),
                    now()->addDay(),
                    static fn (): string => view('theme.brand-variables', [
                        'scale' => $resolver->scale($hex),
                    ])->render(),
                );
            },
        );
    }

    /**
     * نصوص الجولة + الـ JS المحزوم (driver.js) في الـ head.
     *
     * ⚠️ `viteTheme()` بيحمّل الـ CSS بس. من غير السطر ده `window.fcStartTour`
     * مكانش متعرّف في اللوحة، والجولات عمرها ما اشتغلت — واختبارات Livewire
     * كلها خضرا لأنها مابتشغّلش JavaScript. رحلة المتصفح هي اللي كشفته
     * (docs/21 بند ٣٠).
     */
    private function registerTourTranslations(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            static fn (): string => view('theme.tour-translations')->render()
                .Blade::render("@vite('resources/js/app.js')"),
        );
    }

    private function registerFooter(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::FOOTER,
            static fn (): string => view('theme.footer')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            static fn (): string => view('theme.environment-badge')->render(),
        );
    }

    /** اسم كلاس صفحة Livewire الحالية — الجولة بتتربط بيه. */
    private static function currentPageClass(): string
    {
        $action = request()->route()?->getAction('controller');

        if (! is_string($action)) {
            return '';
        }

        return str_contains($action, '@') ? explode('@', $action)[0] : $action;
    }

    private function registerBanners(): void
    {
        // شريط الانتحال بيرندره stechstudio/filament-impersonate على نفس الـ hook.

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_AFTER,
            static fn (): string => view('theme.maintenance-banner')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            static fn (): string => Blade::render('@livewire(\'fc.locale-switcher\')'),
        );

        // مشغّل الجولات — بيلاقي جولة الصفحة الحالية لوحده لو فيه واحدة
        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_END,
            static fn (): string => Blade::render(
                '@livewire(\'fc-onboarding.tour-runner\', [\'page\' => $page])',
                ['page' => self::currentPageClass()],
            ),
        );
    }
}
