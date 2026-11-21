<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Src\Support\Application\Contracts\TenantContext;

/**
 * الأساس لكل Job في التطبيق.
 *
 * الطابور مش شايف سياق الطلب — فالسياق بيتنسخ في الكونستركتور
 * وبيترجّع بالـ middleware (docs/03 بند ٤ · docs/13 بند ٢).
 */
abstract class ContextAwareJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public ?string $requestId = null;

    public ?int $tenantId = null;

    public ?int $userId = null;

    public function __construct()
    {
        $this->requestId = Log::sharedContext()['request_id'] ?? (string) Str::uuid();
        $this->tenantId = $this->captureTenantId();
        $this->userId = auth()->id();
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new RestoresLogContext];
    }

    private function captureTenantId(): ?int
    {
        try {
            return app(TenantContext::class)->id();
        } catch (\Throwable) {
            return null;
        }
    }
}
