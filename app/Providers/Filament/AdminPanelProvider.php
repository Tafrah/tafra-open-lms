<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Widgets\PlatformShortcuts;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Models\Contracts\FilamentUser;
use Filament\Navigation\NavigationGroup as FilamentNavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use FutureCode\Onboarding\FilamentOnboardingPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Src\Contexts\Ai\Presentation\Filament\Pages\AiConnections;
use Src\Contexts\Identity\Presentation\Filament\Pages\MyDevices;
use Src\Contexts\Identity\Presentation\Filament\Pages\TwoFactorSettings;
use Src\Contexts\Notifications\Presentation\Filament\Pages\NotificationPreferences;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Settings\Domain\Settings\AppearanceSettings;
use Src\Contexts\Settings\Domain\Settings\GeneralSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageMail;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Presentation\Filament\Pages\EditOrganization;
use Src\Contexts\Tenancy\Presentation\Http\Middleware\EnsureTenantIsActive;
use Src\Support\Infrastructure\Theming\BrandLogoResolver;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;
use Src\Support\Presentation\Filament\ContextPanelRegistry;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;
use Src\Support\Presentation\Filament\Pages\Dashboard;
use Src\Support\Presentation\Http\Middleware\AssignRequestContext;
use Src\Support\Presentation\Http\Middleware\EnforceImpersonationTimeout;
use Src\Support\Presentation\Http\Middleware\EnsureTwoFactorIsConfigured;
use Src\Support\Presentation\Http\Middleware\InitializeTenantContext;
use Src\Support\Presentation\Http\Middleware\SecurityHeaders;
use Src\Support\Presentation\Http\Middleware\SetLocale;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard((string) config('authorization.guard'))
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->profile(isSimple: false)

            // ═══════════ الهوية ═══════════
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font(
                fn (): string => $this->settingValue(
                    fn (): string => app(TenantSettings::class)->get('appearance', 'font_family', 'Almarai'),
                    'Almarai',
                ),
                provider: GoogleFontProvider::class,
            )
            ->monoFont('IBM Plex Mono', provider: GoogleFontProvider::class)
            ->colors(fn (): array => app(ThemeColorResolver::class)->palette())
            ->brandName(fn (): string => $this->settingValue(
                fn (): string => app(GeneralSettings::class)->name(),
                (string) config('app.name'),
            ))
            ->brandLogo(fn (): ?string => app(BrandLogoResolver::class)->url())
            ->darkModeBrandLogo(fn (): ?string => app(BrandLogoResolver::class)->url(dark: true))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn (): ?string => app(BrandLogoResolver::class)->faviconUrl())
            ->darkMode(fn (): bool => $this->settingValue(
                fn (): bool => (bool) app(TenantSettings::class)->get('appearance', 'allow_theme_switch', true),
                true,
            ))
            // defaultThemeMode مابياخدش Closure — بيتقيّم عند بناء اللوحة
            // (مرة لكل طلب)، والقيمة جاية من كاش الإعدادات فرخيصة.
            ->defaultThemeMode(ThemeMode::tryFrom($this->settingValue(
                fn (): string => app(AppearanceSettings::class)->default_theme,
                'system',
            )) ?? ThemeMode::System)
            ->themeSwitcher(fn (): bool => $this->settingValue(
                fn (): bool => (bool) app(TenantSettings::class)->get('appearance', 'allow_theme_switch', true),
                true,
            ))

            // ═══════════ تعدد المستأجرين ═══════════
            ->tenant(Tenant::class, slugAttribute: 'slug')
            ->tenantRoutePrefix('t')
            // الإيقاف بيتفرض على الرابط نفسه — الاختفاء من قائمة التبديل UX بس (docs/24 بند ٤)
            ->tenantMiddleware([InitializeTenantContext::class, EnsureTenantIsActive::class], isPersistent: true)
            ->tenantProfile(EditOrganization::class)

            // ═══════════ التنقّل ═══════════
            ->navigationGroups($this->navigationGroups())
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            // مقاسات «طفرة البسيط»: 264px مفتوح، 76px أيقونات بس
            ->sidebarWidth('16.5rem')
            ->collapsedSidebarWidth('4.75rem')
            ->maxContentWidth(Width::Full)
            // مدرس واحد لكل تثبيت — مفيش قايمة تبديل مؤسسات؛ والبحث عن الصفحات من السايدبار
            ->tenantMenu(false)
            ->globalSearch(false)
            ->unsavedChangesAlerts()
            ->spa()

            // ═══════════ الإشعارات ═══════════
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')

            // ═══════════ الصفحات والودجتس ═══════════
            ->pages([Dashboard::class])
            ->widgets([PlatformShortcuts::class])

            // الأونبوردنج بيركّب نفسه: صفحاته وودجته بيتسجّلوا من جوه
            // الباكدج حسب `config/fc-onboarding.php` (docs/15 بند ٢).
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
                InitializeTenantContext::class,
                AssignRequestContext::class,
                SecurityHeaders::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
                EnforceImpersonationTimeout::class,
                EnsureTwoFactorIsConfigured::class,
            ], isPersistent: true)
            ->userMenuItems($this->userMenuItems());

        $panel = $this->simpleShell($panel);

        // كل سياق بيضيف موارده وصفحاته وودجتسه من مساره الخاص
        return app(ContextPanelRegistry::class)->applyTo($panel);
    }

    /**
     * غلاف «طفرة البسيط» (LMS-Admin-ReactJS → themes/simple): رأس السايدبار بالاسم
     * واللوجو، البحث في الصفحات، القاع (الإعلانات، المرشد، المستخدم)، روابط الهيدر
     * السريعة وبوابة الطلاب، وتنبيه التحقق بخطوتين على الرئيسية. الشكل في
     * resources/css/filament/admin/simple.css.
     */
    private function simpleShell(Panel $panel): Panel
    {
        return $panel
            ->renderHook(PanelsRenderHook::SIDEBAR_LOGO_BEFORE, static fn (): string => view('theme.simple.sidebar-head')->render())
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, static fn (): string => view('theme.simple.sidebar-filter')->render())
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, static fn (): string => view('theme.simple.sidebar-foot')->render())
            ->renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER, static fn (): string => view('theme.simple.topbar-links')->render())
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, static fn (): string => view('theme.simple.topbar-actions')->render())
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, static fn (): string => view('theme.simple.theme-toggle')->render())
            ->renderHook(PanelsRenderHook::PAGE_START, static fn (): string => view('theme.simple.two-factor-banner')->render(), scopes: Dashboard::class);
    }

    /** @return array<int, FilamentNavigationGroup> */
    private function navigationGroups(): array
    {
        return collect(NavigationGroup::cases())
            ->sortBy(fn (NavigationGroup $group): int => $group->sort())
            // المجموعة بتختفي لوحدها لو كل عناصرها مخفية. حماية المنطقة
            // نفسها (`access.tenancy` / `access.system`) في الـ Policy عبر
            // `Policy::area()` — الإخفاء UX، الـ Policy هي الأمان.
            ->map(fn (NavigationGroup $group): FilamentNavigationGroup => FilamentNavigationGroup::make()
                ->label(fn (): string => $group->getLabel())
                ->icon($group->getIcon())
                ->collapsible($group->folded())
                ->collapsed($group->folded()))
            ->values()
            ->all();
    }

    /** @return array<string, Action> */
    private function userMenuItems(): array
    {
        return [
            'profile' => Action::make('profile')
                ->label(fn (): string => auth()->user()->name ?? __('common.profile'))
                ->icon('heroicon-o-user-circle'),

            // قايمة المؤسسات مقفولة (مدرس واحد) — ملف المؤسسة (الاسم واللون) من هنا
            'organization_profile' => Action::make('organization_profile')
                ->label(fn (): string => EditOrganization::getLabel())
                ->icon('phosphor-buildings-bold')
                ->url(fn (): ?string => ($tenant = Filament::getTenant()) !== null ? EditOrganization::getUrl(tenant: $tenant) : null)
                ->visible(fn (): bool => Filament::getTenant() !== null && Gate::allows('access.organization_profile')),

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

            'notification_preferences' => Action::make('notification_preferences')
                ->label(fn (): string => __('notifications::notifications.menu.preferences'))
                ->icon('heroicon-o-bell-alert')
                ->url(fn (): string => NotificationPreferences::getUrl())
                ->visible(fn (): bool => Gate::allows('access.notification_preferences')),

            // Claude / ChatGPT عن طريق MCP (docs/23) — الصفحة نفسها بتقرر تفتح لمين
            'ai_connections' => Action::make('ai_connections')
                ->label(fn (): string => __('ai::ai.menu.connections'))
                ->icon('heroicon-o-sparkles')
                ->url(fn (): string => AiConnections::getUrl())
                ->visible(fn (): bool => AiConnections::canAccess()),

            // صاحب المنصة: البريد (عشان «نسيت كلمة السر») والنسخ الاحتياطي في اللوحة المركزية
            'platform_settings' => Action::make('platform_settings')
                ->label(fn (): string => __('settings::settings.menu.platform'))
                ->icon('heroicon-o-envelope')
                ->url(fn (): string => ManageMail::getUrl(panel: 'central'))
                ->visible(fn (): bool => ($user = auth()->user()) instanceof FilamentUser
                    && $user->canAccessPanel(Filament::getPanel('central'))),

            'logout' => Action::make('logout')->label(fn (): string => __('common.logout')),
        ];
    }

    /**
     * الإعدادات بتتقرا من قاعدة البيانات — واللوحة لازم تشتغل حتى قبل
     * أول `migrate`. أي فشل بيرجّع الافتراضي بدل ما يكسر الصفحة.
     *
     * @template T
     *
     * @param \Closure(): T $resolver
     * @param T $fallback
     * @return T
     */
    private function settingValue(\Closure $resolver, mixed $fallback): mixed
    {
        try {
            return $resolver();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
