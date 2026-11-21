<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Application\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Src\Contexts\Content\Application\Queries\TenantContentSummary;
use Src\Contexts\Identity\Application\Queries\TenantUserSummary;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Notifications\Infrastructure\Notifications\WeeklyDigestNotification;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Queue\ContextAwareJob;
use Throwable;

/**
 * الملخص الأسبوعي لمؤسسة واحدة.
 *
 * نموذج الـ Job المعياري في القالب (docs/13 بند ٢):
 * `tenantId` صريح · `$tries`/`$timeout`/`$backoff` محددين · `failed()` موجودة ·
 * `ShouldBeUnique` عبر `uniqueId()` · بيمرّر IDs مش موديلات.
 *
 * الإحصائيات بتتجمّع من **الواجهات العامة** للسياقات التانية، مش من
 * موديلاتها — عشان قاعدة عدم استيراد السياقات لبعض تفضل محفوظة.
 */
final class GenerateWeeklyDigestJob extends ContextAwareJob
{
    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public int $maxExceptions = 2;

    public function __construct(public readonly int $digestTenantId)
    {
        parent::__construct();

        $this->tenantId = $digestTenantId;
        $this->onQueue('default');
    }

    public function handle(
        TenantContext $context,
        TenantUserSummary $users,
        TenantContentSummary $content,
    ): void {
        $context->set($this->digestTenantId);

        $tenant = Tenant::query()->find($this->digestTenantId);

        if ($tenant === null || ! $tenant->is_active) {
            return;
        }

        $since = now()->subWeek();

        $stats = [
            ...$users->forTenant($this->digestTenantId, $since),
            ...$content->forTenant($this->digestTenantId, $since),
        ];

        User::query()
            ->ofTenant($this->digestTenantId)
            ->active()
            ->chunkById(200, function (Collection $recipients) use ($tenant, $stats): void {
                Notification::send(
                    $recipients,
                    new WeeklyDigestNotification((string) $tenant->name, $stats),
                );
            });
    }

    public function uniqueId(): string
    {
        return "weekly-digest:{$this->digestTenantId}:".now()->format('o-W');
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('audit')->error('فشل توليد الملخص الأسبوعي', [
            'tenant_id' => $this->digestTenantId,
            'error' => $exception->getMessage(),
        ]);
    }
}
