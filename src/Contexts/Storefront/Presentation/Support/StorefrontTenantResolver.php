<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Src\Contexts\Storefront\Application\Queries\SiteSettings;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/**
 * مين المؤسسة صاحبة الموقع العام؟ في single: مؤسسة واحدة — بنطابق
 * الدومين لو متسجّل، وإلا أول مؤسسة نشطة.
 *
 * بيستخدمه `ResolveStorefrontTenant` (طلبات الصفحات) ومكوّنات Livewire
 * بتاعة الموقع (طلبات `/livewire/update` — شوف `StorefrontServiceProvider`).
 */
final class StorefrontTenantResolver
{
    public function resolve(Request $request): ?Tenant
    {
        $active = Tenant::query()->active()->orderBy('id');

        $byDomain = (clone $active)->where('domain', mb_strtolower($request->getHost()))->first();

        if ($byDomain instanceof Tenant) {
            return $byDomain;
        }

        $first = $active->first();

        return $first instanceof Tenant ? $first : null;
    }

    /** بيضبط سياق المؤسسة ويشارك `$academy` و`$site` مع كل الـ views. */
    public function apply(Tenant $tenant): void
    {
        app(TenantContext::class)->set((int) $tenant->getKey());

        View::share('academy', $tenant);
        View::share('site', app(SiteSettings::class)->load((string) $tenant->getTranslation('name', app()->getLocale(), true)));
    }
}
