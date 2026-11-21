<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

final readonly class ActivateTenantAction
{
    public function handle(Tenant $tenant, ?User $actor = null): Tenant
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('activate', $tenant);
        }

        $tenant->forceFill([
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($tenant)
            ->log('tenant_activated');

        return $tenant;
    }
}
