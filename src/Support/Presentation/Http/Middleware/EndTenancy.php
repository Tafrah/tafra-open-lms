<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Support\Application\Contracts\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * ستاك اللوحة المركزية: مفيش مؤسسة، والفريق = الفريق المركزي (docs/24 بند ١).
 *
 * ⚠️ مش `InitializeTenantContext`: ده بيختار «أول مؤسسة نشطة للمستخدم»
 * لو مفيش مؤسسة في الرابط — في اللوحة المركزية كان هيحط فريق التشغيل
 * في سياق مؤسسة عشوائية، وأدواره المركزية تختفي.
 */
final class EndTenancy
{
    public function handle(Request $request, Closure $next): Response
    {
        app(TenantContext::class)->set(null);

        return $next($request);
    }
}
