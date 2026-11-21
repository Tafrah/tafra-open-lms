<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

/**
 * إيقاف مؤقت — مش حذف. المؤسسة بتختفي من قائمة التبديل ورابطها
 * بيعرض صفحة الإيقاف، والبيانات كلها زي ما هي (docs/24 بند ٤).
 */
final readonly class SuspendTenantAction
{
    public function handle(Tenant $tenant, string $reason, ?User $actor = null): Tenant
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('suspend', $tenant);
        }

        $tenant->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();

        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($tenant)
            ->withProperties(['reason' => $reason])
            ->log('tenant_suspended');

        return $tenant;
    }
}
