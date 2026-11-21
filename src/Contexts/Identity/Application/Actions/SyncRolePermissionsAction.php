<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Infrastructure\Authorization\PermissionBuilder;

final readonly class SyncRolePermissionsAction
{
    public function __construct(
        private PermissionBuilder $builder,
        private PermissionRegistrar $registrar,
    ) {}

    /**
     * @param array<string, list<string>> $groups
     * @return list<string>
     */
    public static function flatten(array $groups): array
    {
        $flat = [];

        foreach ($groups as $permissions) {
            $flat = [...$flat, ...(array) $permissions];
        }

        return array_values(array_unique($flat));
    }

    /** @param  list<string>  $permissions */
    public function handle(Role $role, array $permissions, ?User $actor = null): void
    {
        if ($actor !== null) {
            Gate::forUser($actor)->authorize('update', $role);
        }

        // الكونفيج هو المصدر الوحيد — أي اسم مش فيه بيتشال بهدوء
        $allowed = array_values(array_intersect($this->builder->allPermissionNames(), $permissions));

        $role->syncPermissions($allowed);

        $this->registrar->forgetCachedPermissions();

        activity('security')
            ->performedOn($role)
            ->withProperties(['permissions' => $allowed])
            ->log('role_permissions_synced');
    }
}
