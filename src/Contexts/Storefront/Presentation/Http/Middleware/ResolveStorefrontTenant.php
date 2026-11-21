<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Contexts\Storefront\Presentation\Support\StorefrontTenantResolver;
use Symfony\Component\HttpFoundation\Response;

/**
 * المؤسسة بتاعة الموقع العام — **قبل** أي استعلام على موديلات المؤسسة
 * (الطالب نفسه `BelongsToTenant`). من غير مؤسسة → صفحة «لسه بنجهّز».
 */
final class ResolveStorefrontTenant
{
    public function __construct(private readonly StorefrontTenantResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolver->resolve($request);

        if ($tenant === null) {
            return response()->view('storefront::errors.no-academy', [], 503);
        }

        $this->resolver->apply($tenant);

        return $next($request);
    }
}
