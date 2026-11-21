<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Support\Application\Contracts\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * أي سطر لوج من غير سياق = سطر ضايع (docs/11 بند ٢).
 * الـ request_id بيرجع في الرد كمان عشان الدعم يربط الشكوى باللوج.
 */
final class AssignRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // الميدلوير ده بيتسجّل مرتين (ستاك الويب العام + ستاك اللوحة الدائم).
        // من غير إعادة استخدام المعرّف، السطور اللي اتكتبت بين التشغيلتين
        // بتحمل request_id مختلف عن اللي راجع في الرد.
        $requestId = $request->header('X-Request-Id')
            ?: (Log::sharedContext()['request_id'] ?? null)
            ?: (string) Str::uuid();

        Log::shareContext([
            'request_id' => $requestId,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'tenant_id' => app(TenantContext::class)->isBypassed() ? null : $this->safeTenantId(),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'locale' => app()->getLocale(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    private function safeTenantId(): ?int
    {
        try {
            return app(TenantContext::class)->id();
        } catch (\Throwable) {
            return null;
        }
    }
}
