<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Src\Support\Application\Contracts\TenantContext;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * سجل النشاط.
 *
 * `tenant_id` و`ip` و`request_id` بيتحقنوا هنا لكل سجل — عشان
 * activitylog v5 مفيهوش hook زي `tapActivity()`، والإثراء لازم يكون
 * في مكان واحد يغطّي السجلات اليدوية والتلقائية (docs/11 بند ٤).
 *
 * مركزي في الوضعين: التدقيق لازم يعيش بعد حذف قاعدة مؤسسة، واللوحة
 * المركزية بتعرضه كامل (docs/03 بند ٣).
 */
final class ActivityLog extends SpatieActivity
{
    use CentralConnection;

    protected static function booted(): void
    {
        self::creating(static function (self $activity): void {
            $properties = collect($activity->properties ?? []);

            $activity->properties = $properties->merge(array_filter([
                'tenant_id' => $properties->get('tenant_id') ?? self::currentTenantId(),
                'ip' => $properties->get('ip') ?? request()->ip(),
                'request_id' => $properties->get('request_id') ?? (Log::sharedContext()['request_id'] ?? null),
            ], static fn (mixed $value): bool => $value !== null));
        });
    }

    /**
     * @param Builder<ActivityLog> $query
     * @return Builder<ActivityLog>
     */
    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where('properties->tenant_id', $tenantId);
    }

    public function tenantId(): ?int
    {
        $value = $this->properties['tenant_id'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    private static function currentTenantId(): ?int
    {
        try {
            return app(TenantContext::class)->id();
        } catch (\Throwable) {
            return null;
        }
    }
}
