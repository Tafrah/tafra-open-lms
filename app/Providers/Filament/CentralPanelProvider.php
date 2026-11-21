<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Actions\Action;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup as FilamentNavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Platform;
use Filament\Support\Enums\Width;
use FutureCode\Onboarding\FilamentOnboardingPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource;
use Src\Contexts\Identity\Presentation\Filament\Pages\MyDevices;
use Src\Contexts\Identity\Presentation\Filament\Pages\TwoFactorSettings;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;
use Src\Support\Presentation\Filament\ContextPanelRegistry;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;
use Src\Support\Presentation\Http\Middleware\AssignRequestContext;
use Src\Support\Presentation\Http\Middleware\EndTenancy;
use Src\Support\Presentation\Http\Middleware\EnforceImpersonationTimeout;
use Src\Support\Presentation\Http\Middleware\EnsureTwoFactorIsConfigured;
use Src\Support\Presentation\Http\Middleware\SecurityHeaders;
use Src\Support\Presentation\Http\Middleware\SetLocale;

/**
 * اللوحة المركزية `/central` — فريق التشغيل بيتحكم في المؤسسات والنظام
 * (docs/24). مفيش `->tenant()`: السياق دايماً مركزي والأدوار على الفريق `0`.
 *
 * الدخول: `canAccessPanel('central')` → Gate `access.panel.central`.
 * مدير المؤسسة (حتى `admin`) مالوش الصلاحية دي على الفريق المركزي → 403.
 */
final class CentralPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('central')
            ->path('central')
            ->authGuard((string) config('authorization.guard'))
            ->login()

            // ═══════════ الهوية ═══════════
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('Almarai', provider: GoogleFontProvider::class)
            ->monoFont('IBM Plex Mono', provider: GoogleFontProvider::class)
            ->colors(fn (): array => app(ThemeColorResolver::class)->palette())
            ->brandName(fn (): string => __('tenancy::tenancy.central.brand'))

            // ═══════════ التنقّل ═══════════
            ->navigationGroups($this->navigationGroups())
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->sidebarWidth('17rem')
            ->collapsedSidebarWidth('4.5rem')
            ->maxContentWidth(Width::Full)
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldSuffix(fn (): string => Platform::detect() === Platform::Mac ? '⌘K' : 'Ctrl+K')
            ->unsavedChangesAlerts()
            ->spa()
            ->databaseNotifications()

            // ═══════════ الصفحات ═══════════
            // صفحات الحساب مشتركة بين اللوحتين — الـ 2FA إجباري لفريق التشغيل
            ->pages([Dashboard::class, TwoFactorSettings::class, MyDevices::class])
            // سجل النشاط الكامل: من غير مؤسسة، `forTenant(null)` بيعرض الكل
            ->resources([ActivityLogResource::class])

            // معالج وقائمة مهام بخطوات اللوحة المركزية بس (`panel()` = central)
            ->plugin(FilamentOnboardingPlugin::make())

            // ═══════════ الميدلوير ═══════════
            // الكوكيز والجلسة مش persistent: مسار تحديث Livewire بيشغّل مجموعة web أصلًا،
            // وإعادتها كانت بتفك تشفير كوكي الجلسة مرتين ← جلسة جديدة فاضية ← تسجيل خروج
            // بعد أول طلب Livewire (ودجتس لوحة المعلومات مثلًا)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
            ])
            ->middleware([
                AuthenticateSession::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
                EndTenancy::class,
                AssignRequestContext::class,
                SecurityHeaders::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
                // نفس حد الانتحال بتاع لوحة المؤسسة — من غيره الجلسة هنا مالهاش نهاية
                EnforceImpersonationTimeout::class,
                EnsureTwoFactorIsConfigured::class,
            ], isPersistent: true)
            ->navigationItems($this->externalNavigationItems())
            ->userMenuItems([
                // صفحات الحساب مسجّلة في اللوحتين — من غير الرابط ده «أجهزتي» مالهاش طريق
                // في /central غير كتابة الرابط (ScreensJourneyTest كشفها)
                'devices' => Action::make('devices')
                    ->label(fn (): string => __('identity::identity.menu.devices'))
                    ->icon('heroicon-o-device-phone-mobile')
                    ->url(fn (): string => MyDevices::getUrl())
                    ->visible(fn (): bool => Gate::allows('access.my_devices')),

                'two_factor' => Action::make('two_factor')
                    ->label(fn (): string => __('identity::identity.menu.two_factor'))
                    ->icon('heroicon-o-shield-check')
                    ->url(fn (): string => TwoFactorSettings::getUrl())
                    ->visible(fn (): bool => Gate::allows('access.two_factor')),

                'logout' => Action::make('logout')->label(fn (): string => __('common.logout')),
            ]);

        return app(ContextPanelRegistry::class)->applyTo($panel);
    }

    /** @return array<int, FilamentNavigationGroup> */
    private function navigationGroups(): array
    {
        return collect(NavigationGroup::cases())
            ->sortBy(fn (NavigationGroup $group): int => $group->sort())
            ->map(fn (NavigationGroup $group): FilamentNavigationGroup => FilamentNavigationGroup::make()
                ->label(fn (): string => $group->getLabel())
                ->icon($group->getIcon())
                ->collapsible())
            ->values()
            ->all();
    }

    /**
     * Horizon و Pulse بيراقبوا النظام كله — مكانهم هنا مش في لوحة مؤسسة
     * (docs/24 بند ٧)، وكل واحد وراه Gate معرّف (docs/13 بند ١).
     *
     * @return array<int, NavigationItem>
     */
    private function externalNavigationItems(): array
    {
        return [
            NavigationItem::make(fn (): string => __('navigation.items.horizon'))
                ->url(fn (): string => url('horizon'), shouldOpenInNewTab: true)
                ->icon('heroicon-o-queue-list')
                ->group(fn (): string => NavigationGroup::System->getLabel())
                ->sort(97)
                ->visible(fn (): bool => Gate::allows('access.horizon')),

            NavigationItem::make(fn (): string => __('navigation.items.pulse'))
                ->url(fn (): string => url('pulse'), shouldOpenInNewTab: true)
                ->icon('heroicon-o-chart-bar-square')
                ->group(fn (): string => NavigationGroup::System->getLabel())
                ->sort(98)
                ->visible(fn (): bool => Gate::allows('access.pulse')),
        ];
    }
}
