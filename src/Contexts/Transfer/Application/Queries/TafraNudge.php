<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Application\Queries;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Src\Support\Application\Contracts\TenantContext;

/**
 * رسالة «منصتك كبرت» على الرئيسية — بتظهر بس لما المنصة تعدّي رقم معيّن
 * (طلبة أو إيصالات اتراجعت في الأسبوع)، مش إعلان دايم (plan بند ٩).
 * الأرقام بتتحسب مرة في اليوم.
 */
final readonly class TafraNudge
{
    public function __construct(private TenantContext $tenant) {}

    /** @return array{kind: 'students'|'receipts', count: int}|null */
    public function current(): ?array
    {
        $tenantId = $this->tenant->id();

        if ($tenantId === null || ! (bool) config('transfer.tafra.show_features', true)) {
            return null;
        }

        /** @var array{students: int, receipts: int} $counts */
        $counts = Cache::remember("tafra-nudge:{$tenantId}:".now()->toDateString(), now()->addDay(), static fn (): array => [
            'students' => DB::table('students')->where('tenant_id', $tenantId)->whereNull('deleted_at')->count(),
            'receipts' => DB::table('payment_invoices')->where('tenant_id', $tenantId)
                ->where('reviewed_at', '>=', now()->subWeek())->count(),
        ]);

        $thresholds = (array) config('transfer.tafra.nudge');

        if ($counts['receipts'] >= (int) ($thresholds['receipts_per_week'] ?? PHP_INT_MAX)) {
            return ['kind' => 'receipts', 'count' => $counts['receipts']];
        }

        if ($counts['students'] >= (int) ($thresholds['students'] ?? PHP_INT_MAX)) {
            return ['kind' => 'students', 'count' => $counts['students']];
        }

        return null;
    }
}
