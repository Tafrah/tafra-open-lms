<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Queue;

use Closure;
use Illuminate\Support\Facades\Log;
use Src\Support\Application\Contracts\TenantContext;

/**
 * بيرجّع سياق الطلب قبل تنفيذ الـ Job — فالسلسلة من الطلب للـ Job
 * كلها بنفس الـ request_id في اللوج (docs/11 بند ٢).
 */
final class RestoresLogContext
{
    public function handle(object $job, Closure $next): mixed
    {
        if (property_exists($job, 'tenantId')) {
            app(TenantContext::class)->set($job->tenantId);
        }

        Log::shareContext(array_filter([
            'request_id' => $job->requestId ?? null,
            'user_id' => $job->userId ?? null,
            'tenant_id' => $job->tenantId ?? null,
            'job' => $job::class,
        ], static fn (mixed $value): bool => $value !== null));

        return $next($job);
    }
}
