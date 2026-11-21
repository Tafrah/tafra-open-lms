<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Presentation\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * المؤسسة الموقوفة بتختفي من قائمة التبديل — لكن الإخفاء UX مش أمان.
 * الرابط المباشر `/admin/t/{slug}` بيوصل هنا وبيرجّع صفحة الإيقاف
 * مترجمة، مش البيانات ولا 404 (docs/24 بند ٤).
 *
 * فريق التشغيل بيعدّي وهو منتحل — عشان الدعم يقدر يشوف المشكلة.
 */
final class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        // المعطّلة (`is_active = false`) زي الموقوفة: اختفاؤها من التبديل كان UX بس
        if (! $tenant instanceof Tenant || ($tenant->is_active && ! $tenant->isSuspended())) {
            return $next($request);
        }

        // فريق التشغيل بس — أي انتحال تاني (مدير مؤسسة نشطة بينتحل عضو مشترك)
        // كان بيفتح بيانات المؤسسة الموقوفة
        if ($this->impersonatedByStaff()) {
            return $next($request);
        }

        return response()->view('tenancy::suspended', ['tenant' => $tenant], Response::HTTP_FORBIDDEN);
    }

    private function impersonatedByStaff(): bool
    {
        $impersonation = app(ImpersonationContext::class);

        if (! $impersonation->isActive()) {
            return false;
        }

        // من غير global scopes: جوه طلب مؤسسة `User` مفلتر بأعضائها، وفريق التشغيل
        // مش عضو — فالبحث العادي كان بيرجّع null والاستثناء مابيشتغلش أبداً
        $impersonator = User::query()->withoutGlobalScopes()->whereKey($impersonation->impersonatorId())->first();

        return $impersonator instanceof User && app(CentralRoles::class)->isStaff($impersonator);
    }
}
