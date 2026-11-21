<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Tenancy;

use Filament\Facades\Filament;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Stancl\Tenancy\Tenancy;
use Throwable;

/**
 * التنفيذ الفعلي لسياق المستأجر — واجهة فوق stancl/tenancy.
 *
 * كل التطبيق بيسأل `TenantContext`، وstancl هو المحرّك: `set()` بتنادي
 * `tenancy()->initialize()`، واللي بتشغّل الـ bootstrappers حسب الوضع
 * (الصلاحيات والكاش واللوج في الوضعين، وتبديل القاعدة في multi).
 * كده أي طريق لتبديل المستأجر — طلب، Job، أمر، أو stancl نفسه —
 * بيوصل لنفس الحالة (docs/03 بند ٣ · docs/21 بند ٣٠).
 */
final class ScopedTenantContext implements TenantContext
{
    private bool $bypassed = false;

    /** هل حاولنا نستنتج المستأجر من البيئة (Filament) قبل كده؟ */
    private bool $resolved = false;

    public function __construct(
        private readonly Tenancy $tenancy,
        private readonly TenantContextBootstrapper $bootstrapper,
    ) {}

    public function id(): ?int
    {
        if ($this->tenancy->initialized && $this->tenancy->tenant !== null) {
            return (int) $this->tenancy->tenant->getTenantKey();
        }

        if (! $this->resolved) {
            $this->resolved = true;

            // لو Filament عرّف المستأجر، خده — من غير ما نلفّ على الـ DB
            $tenant = Filament::getTenant();

            if ($tenant instanceof Tenant) {
                $this->tenancy->initialize($tenant);

                return (int) $tenant->getKey();
            }
        }

        return null;
    }

    public function set(?int $tenantId): void
    {
        $this->resolved = true;

        if ($tenantId === null) {
            if ($this->tenancy->initialized) {
                $this->tenancy->end();
            } else {
                // مفيش tenancy نشطة، بس لازم فريق spatie يرجع للسياق المركزي
                $this->bootstrapper->sync(null);
            }

            return;
        }

        if ($this->tenancy->initialized && (int) $this->tenancy->tenant?->getTenantKey() === $tenantId) {
            // نفس المستأجر: stancl مابيعيدش التهيئة، بس فريق spatie ممكن
            // يكون اتغيّر من برا (seeder، اختبار) — نزامن مرة
            $this->bootstrapper->sync($tenantId);

            return;
        }

        $this->tenancy->initialize($this->findTenant($tenantId));
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    public function withoutScope(callable $callback): mixed
    {
        $previous = $this->bypassed;
        $this->bypassed = true;

        try {
            return $callback();
        } finally {
            $this->bypassed = $previous;
        }
    }

    public function forTenant(?int $tenantId, callable $callback): mixed
    {
        $previous = $this->currentId();
        $previouslyResolved = $this->resolved;

        $this->set($tenantId);

        try {
            return $callback();
        } finally {
            $this->set($previous);
            $this->resolved = $previouslyResolved;
        }
    }

    public function forEachTenant(callable $callback): void
    {
        // Tenant نفسه مش تابع لمستأجر — مفيش scope نتجاوزه
        // الجاهزة بس: مؤسسة قاعدتها ماتعملتش كانت بترمي وتوقف اللفة لكل اللي بعدها
        $tenants = Tenant::query()
            ->where('is_active', true)
            ->whereNull('suspended_at')
            ->where('provisioning_status', Tenant::PROVISIONING_READY)
            ->orderBy('id')
            ->get();

        $previous = $this->currentId();

        try {
            foreach ($tenants as $tenant) {
                if (! $tenant instanceof Tenant) {
                    continue;
                }

                // فشل مؤسسة واحدة يتسجّل ومايحرمش الباقيين من الشغل المجدول
                try {
                    $this->tenancy->initialize($tenant);
                    $callback($tenant);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        } finally {
            $this->set($previous);
        }
    }

    private function currentId(): ?int
    {
        return $this->tenancy->initialized ? (int) $this->tenancy->tenant?->getTenantKey() : null;
    }

    private function findTenant(int $tenantId): Tenant
    {
        $current = Filament::getTenant();

        if ($current instanceof Tenant && (int) $current->getKey() === $tenantId) {
            return $current;
        }

        // المحذوف حذف ناعم لسه مؤسسة — الاستعادة والتدقيق محتاجين سياقه
        $tenant = Tenant::query()->withTrashed()->find($tenantId);

        if (! $tenant instanceof Tenant) {
            throw new TenantCouldNotBeIdentifiedById($tenantId);
        }

        return $tenant;
    }
}
