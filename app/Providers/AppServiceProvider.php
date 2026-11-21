<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\FailedImportRow;
use Filament\Actions\Imports\Models\Import;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Src\Contexts\Settings\Domain\Settings\SecuritySettings;
use Src\Support\Application\Contracts\Clock;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Exceptions\DomainException;
use Src\Support\Infrastructure\Authorization\PolicyMemo;
use Src\Support\Infrastructure\Filesystem\SettingsDrivenDiskResolver;
use Src\Support\Infrastructure\Persistence\Filament\CentralExport;
use Src\Support\Infrastructure\Persistence\Filament\CentralFailedImportRow;
use Src\Support\Infrastructure\Persistence\Filament\CentralImport;
use Src\Support\Infrastructure\Tenancy\ScopedTenantContext;
use Src\Support\Infrastructure\Tenancy\SystemClock;
use Src\Support\Presentation\Filament\ContextPanelRegistry;
use Src\Support\Presentation\Mcp\McpRegistry;
use Src\Support\Presentation\Mcp\McpSession;
use Src\Support\Presentation\Mcp\SystemMap\SystemMap;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // لازم singleton: كل سياق بيسجّل نفسه فيه في register()،
        // واللوحة بتقرا منه في boot().
        $this->app->singleton(ContextPanelRegistry::class);

        // نفس الفكرة لخادم MCP: السياقات بتسجّل إعلاناتها، والخادم بيقرا (docs/23 بند ٢).
        // الجلسة `scoped`: مين بينادي بيتصفّر مع كل طلب — مايعيشش لعمر العامل.
        $this->app->singleton(McpRegistry::class);
        $this->app->scoped(McpSession::class);
        $this->app->scoped(SystemMap::class);

        $this->app->singleton(TenantContext::class, ScopedTenantContext::class);
        $this->app->singleton(DiskResolver::class, SettingsDrivenDiskResolver::class);
        $this->app->singleton(Clock::class, SystemClock::class);

        // `scoped` مش `singleton`: الذاكرة دي لكل طلب — تحت Octane
        // بتتفضّى تلقائياً بين الطلبات.
        $this->app->scoped(PolicyMemo::class);

        // جداول التصدير والاستيراد مركزية. Filament بيحلّ الموديلات من الحاوية،
        // فالربط ده بيثبّت الاتصال في كل المسارات (docs/03 بند ٣).
        $this->app->bind(Export::class, CentralExport::class);
        $this->app->bind(Import::class, CentralImport::class);
        $this->app->bind(FailedImportRow::class, CentralFailedImportRow::class);
    }

    public function boot(): void
    {
        $this->reportDomainErrorsAsNotifications();
        $this->configureTrustedProxies();
        $this->configureModels();
        $this->configureDates();
        $this->configureUrls();
        $this->configurePasswords();
        $this->configureRateLimiting();
        $this->configureTables();
    }

    /**
     * قائمة البروكسيات الموثوقة.
     *
     * هنا مش في `bootstrap/app.php`: الـ closure هناك بتشتغل قبل ما الكونفيج
     * يتحمّل، و`env()` بترجّع null بعد `config:cache` — يعني CIDR صريح كان
     * بيتحوّل بصمت لـ `*` في الإنتاج (docs/14 بند ١ · docs/21 بند ١٦).
     */
    private function configureTrustedProxies(): void
    {
        $proxies = config('security.trusted_proxies');

        if (is_string($proxies) || is_array($proxies)) {
            TrustProxies::at($proxies);
        }
    }

    /**
     * الصرامة مفعّلة في كل البيئات ما عدا الإنتاج — بما فيها الاختبارات،
     * عشان الـ CI يمسك الـ N+1 قبل ما يوصل للإنتاج (بدل ما يبقى أخضر
     * والإنتاج بيرمي 500).
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::automaticallyEagerLoadRelationships(false);
        Model::unguard(false);
    }

    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    private function configureUrls(): void
    {
        // HTTPS إجباري بس لما الموقع نفسه على https — استضافة مشتركة لسه من غير
        // شهادة كانت بتفتح اللوحة من غير CSS ولا JS (كل الروابط https ومفيش SSL)
        if ($this->app->isProduction() && (bool) config('app.force_https')) {
            URL::forceScheme('https');
        }
    }

    private function configurePasswords(): void
    {
        Password::defaults(function (): Password {
            $settings = $this->securitySettings();

            $rule = Password::min($settings['min_length'])
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            return $this->app->isProduction() && $settings['uncompromised']
                ? $rule->uncompromised()
                : $rule;
        });
    }

    /** @return array{min_length: int, uncompromised: bool} */
    private function securitySettings(): array
    {
        try {
            $settings = $this->app->make(SecuritySettings::class);

            return [
                'min_length' => $settings->password_min_length,
                'uncompromised' => $settings->password_requires_uncompromised,
            ];
        } catch (\Throwable) {
            return [
                'min_length' => (int) config('security.password.min_length', 12),
                'uncompromised' => (bool) config('security.password.uncompromised', true),
            ];
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request): array => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('two-factor', fn (Request $request): Limit => Limit::perMinute(5)
            ->by((string) ($request->session()->get('login.id') ?? $request->ip())));

        RateLimiter::for('exports', fn (Request $request): Limit => Limit::perHour(10)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('media-upload', fn (Request $request): Limit => Limit::perMinute(20)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }

    /**
     * الإعدادات الافتراضية لكل الجداول — مرة واحدة بدل ٦٠ مورد.
     * متغيّرهاش في مورد فردي غير بتعليق يشرح ليه (docs/08 بند ١).
     */
    private function configureTables(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultPaginationPageOption(25)
                ->paginated([10, 25, 50, 100])
                ->extremePaginationLinks()
                ->persistFiltersInSession()
                ->persistSortInSession()
                ->persistSearchInSession()
                ->persistColumnSearchesInSession()
                ->persistColumnsInSession()
                ->deferLoading()
                ->deferFilters()
                ->searchOnBlur()
                ->striped()
                ->reorderableColumns()
                ->emptyStateHeading(__('table.empty.heading'))
                ->emptyStateDescription(__('table.empty.description'))
                ->emptyStateIcon('heroicon-o-inbox');
        });
    }

    /**
     * خطأ أعمال من زرار في اللوحة (رصيد مش كفاية، الطالب مشترك بالفعل…) رسالته مترجمة
     * ومقصود يوصل للمستخدم — بيظهر إشعار أحمر بدل صفحة 500.
     */
    private function reportDomainErrorsAsNotifications(): void
    {
        Livewire::listen('exception', function (object $component, \Throwable $e, callable $stopPropagation): void {
            if (! $e instanceof DomainException) {
                return;
            }

            Notification::make()->danger()->title($e->getMessage())->send();
            $stopPropagation();
        });
    }
}
