<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;

/**
 * الأدوار مربوطة بالمؤسسة (teams) — الإسناد لازم يحصل والسياق مضبوط،
 * وإلا الربط بيتكتب على المؤسسة الغلط (docs/03 بند ٤).
 */
final readonly class SyncUserRolesAction
{
    public function __construct(
        private TenantContext $tenants,
    ) {}

    /** @param  list<string>  $roles */
    public function handle(User $user, array $roles, ?User $actor = null, ?int $tenantId = null): void
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('manageRoles', $user);
        }

        $tenantId ??= $this->tenants->id();

        $this->tenants->forTenant($tenantId, static function () use ($user, $roles): void {
            $user->unsetRelation('roles');
            $user->syncRoles($roles);
        });

        activity('security')
            ->performedOn($user)
            ->withProperties(['roles' => $roles])
            ->log('roles_synced');
    }
}
