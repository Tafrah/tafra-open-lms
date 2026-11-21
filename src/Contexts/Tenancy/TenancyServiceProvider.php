<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy;

use Filament\Events\TenantSet;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\Health\Facades\Health;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Infrastructure\Backup\RegisterTenantBackupDatabases;
use Src\Contexts\Tenancy\Infrastructure\Health\TenancyModeCheck;
use Src\Contexts\Tenancy\Infrastructure\Health\TenantDatabasesCheck;
use Src\Contexts\Tenancy\Infrastructure\Jobs\MarkTenantProvisioned;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Providers\ContextServiceProvider;
use Src\Support\Infrastructure\Tenancy\TenancyMode;
use Src\Support\Infrastructure\Tenancy\TenancyModeLock;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Events\TenancyEnded;
use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\DeleteDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Stancl\Tenancy\Jobs\SeedDatabase;
use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Stancl\Tenancy\Listeners\RevertToCentralContext;

final class TenancyServiceProvider extends ContextServiceProvider
{
    protected function name(): string
    {
        return 'tenancy';
    }

    protected function path(): string
    {
        return __DIR__;
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(TenancyModeLock::class);
    }

    protected function bootContext(): void
    {
        // الوضع في .env لازم يطابق الوضع المقفول عند أول نشر (docs/03 بند ١)
        $this->app->make(TenancyModeLock::class)->assertMatches();

        $this->bootStanclEvents();

        // فحوصات الصحة بتتضاف لفحوصات Audit (spatie بيجمعهم) — docs/11 بند ٧
        if (! $this->app->runningUnitTests()) {
            Health::checks([TenancyModeCheck::new(), TenantDatabasesCheck::new()]);
        }

        // قواعد المؤسسات جوه النسخ الاحتياطي — وقت أوامر backup بس (docs/14 بند ٣)
        if ($this->app->runningConsoleCommand('backup:run', 'backup:monitor', 'backup:clean', 'backup:list')) {
            $this->app->make(RegisterTenantBackupDatabases::class)();
        }

        // أي تبديل مستأجر من Filament بيزامن سياقنا فوراً —
        // من غير كده الاستعلامات في نفس الطلب هتفضل على المستأجر القديم.
        Event::listen(TenantSet::class, static function (TenantSet $event): void {
            app(TenantContext::class)->set((int) $event->getTenant()->getKey());
        });
    }

    /**
     * stancl مابيسجّلش مستمعينه لوحده — الـ stub المنشور بتاعه بيعمل ده
     * في `App\Providers`. هنا بدل الـ stub عشان السياق يملك إعداده.
     */
    private function bootStanclEvents(): void
    {
        Event::listen(TenancyInitialized::class, BootstrapTenancy::class);
        Event::listen(TenancyEnded::class, RevertToCentralContext::class);

        // single: مفيش حاجة تتجهّز — المؤسسة جاهزة من لحظة الإنشاء.
        // multi: `pending` لحد ما `MarkTenantProvisioned` يقفل السلسلة.
        Tenant::creating(static function (Tenant $tenant): void {
            if ($tenant->getAttribute('provisioning_status') === null) {
                $tenant->setAttribute('provisioning_status', TenancyMode::isMulti()
                    ? Tenant::PROVISIONING_PENDING
                    : Tenant::PROVISIONING_READY);
            }
        });

        if (! TenancyMode::isMulti()) {
            return;
        }

        Event::listen(
            TenantCreated::class,
            JobPipeline::make([
                CreateDatabase::class,
                MigrateDatabase::class,
                SeedDatabase::class,
                MarkTenantProvisioned::class,
            ])
                ->send(static fn (TenantCreated $event): TenantContract => $event->tenant)
                ->shouldBeQueued((bool) config('tenancy.provisioning.queued'))
                ->toListener(),
        );

        /*
         * ⚠️ مش `TenantDeleted` زي الـ stub: الحدث ده بيتطلق مع الحذف
         * **الناعم** كمان. ربطه بـ DeleteDatabase كان هيمسح قاعدة مؤسسة
         * اتنقلت للمحذوفات بس — والاستعادة بعدها مستحيلة (docs/21 بند ٣٠).
         */
        // `tenants:migrate` بيلف على غير المحذوفين بس — المستعادة كانت بترجع على سكيما قديمة
        Tenant::restored(static function (Tenant $tenant): void {
            Artisan::call('tenants:migrate', [
                '--tenants' => [(string) $tenant->getTenantKey()],
                '--force' => true,
            ]);
        });

        Tenant::forceDeleted(static function (Tenant $tenant): void {
            // `handle()` مباشرة مش `dispatchSync()`: الـ Job عليه SerializesModels،
            // وبعد `forceDeleted` الصف مش موجود — إعادة تحميل الموديل بترمي
            // ModelNotFoundException والقاعدة بتفضل يتيمة (docs/21 بند ٣٠)
            tenancy()->central(static function () use ($tenant): void {
                (new DeleteDatabase($tenant))->handle();
            });
        });
    }
}
