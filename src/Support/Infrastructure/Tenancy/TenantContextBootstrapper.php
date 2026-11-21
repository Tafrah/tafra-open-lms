<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Tenancy;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * كل حاجة لازم تتزامن مع تبديل المستأجر — في الوضعين.
 *
 * كانت `ScopedTenantContext::syncDependents()`. بقت bootstrapper عشان
 * تشتغل بنفس الشكل مهما كان الطريق: `TenantContext::set()`، أو
 * `tenancy()->initialize()` من stancl، أو Job بيرجّع مستأجره
 * (docs/03 بند ٤).
 *
 * ١. فريق spatie — الأدوار مربوطة بالمستأجر. السياق المركزي = فريق
 *    `authorization.central_team_id` (docs/24 بند ٢).
 * ٢. بادئة الكاش — عزل كامل بين المستأجرين.
 * ٣. سياق اللوج.
 */
final class TenantContextBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        $this->sync((int) $tenant->getTenantKey());
    }

    public function revert(): void
    {
        $this->sync(null);
    }

    public function sync(?int $tenantId): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenantId ?? (int) config('authorization.central_team_id'));
        $registrar->forgetCachedPermissions();
        app(CentralRoles::class)->forget();

        // العلاقات المحمّلة على المستخدم الحالي بقت بايتة — لازم تتقرا تاني
        $guard = app(AuthFactory::class)->guard((string) config('authorization.guard'));

        if ($guard->hasUser()) {
            $user = $guard->user();
            $user?->unsetRelation('roles');
            $user?->unsetRelation('permissions');
        }

        $prefix = config('cache.base_prefix', 'fc').'_t'.($tenantId ?? 'global').'_';

        if (config('cache.prefix') !== $prefix) {
            config(['cache.prefix' => $prefix]);
            Cache::forgetDriver(config('cache.default'));
        }

        Log::shareContext(['tenant_id' => $tenantId]);
    }
}
