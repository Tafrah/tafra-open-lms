<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * بيضبط سياق المستأجر لكل طلب.
 *
 * بيتسجّل مرتين عن قصد: في ستاك اللوحة العام (عشان صفحات من غير مستأجر
 * زي الدخول والملف الشخصي تلاقي سياق)، وفي ستاك المستأجر بعد
 * IdentifyTenant (عشان ياخد المستأجر الفعلي من الرابط).
 * الاتنين idempotent.
 */
final class InitializeTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);

        $tenant = Filament::getTenant();

        if ($tenant !== null) {
            $context->set($tenant->getKey());

            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        // الميدلوير ده بيشتغل قبل `Authenticate` وقبل `IdentifyTenant` بتاع Filament —
        // فالمؤسسة لازم تتاخد من الرابط نفسه، مش من أول عضوية للمستخدم. غير كده
        // فحص دخول اللوحة كان بيتسأل على فريق مؤسسة تانية (أو مفيش فريق خالص
        // لمنتحل داخل مؤسسة موقوفة). الإيقاف نفسه بيتفرض بعدين في EnsureTenantIsActive.
        $routeTenant = $this->routeTenant($request, $user);

        if ($routeTenant !== null) {
            $context->set($routeTenant);

            return $next($request);
        }

        // مفيش مؤسسة في الرابط (الملف الشخصي، اختيار المؤسسة) — أول مؤسسة جاهزة ليه
        $context->set($user->tenants()
            ->where('tenants.is_active', true)
            ->whereNull('tenants.suspended_at')
            ->where('tenants.provisioning_status', Tenant::PROVISIONING_READY)
            ->orderBy('tenants.id')
            ->value('tenants.id'));

        return $next($request);
    }

    /** المؤسسة اللي في `{tenant}` لو المستخدم عضو فيها — غير كده `null`. */
    private function routeTenant(Request $request, User $user): ?int
    {
        $slug = $request->route('tenant');

        if ($slug instanceof Tenant) {
            $slug = $slug->getAttribute('slug');
        }

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $attribute = Filament::getCurrentOrDefaultPanel()?->getTenantSlugAttribute() ?? 'slug';

        $id = $user->tenants()->where("tenants.{$attribute}", $slug)->value('tenants.id');

        return $id === null ? null : (int) $id;
    }
}
