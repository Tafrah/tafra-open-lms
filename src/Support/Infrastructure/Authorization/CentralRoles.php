<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\CentralAuthority;

/**
 * أدوار فريق التشغيل — على الفريق المركزي `0` (docs/24 بند ٢).
 *
 * ليه مش `hasRole()`؟ لأن `hasRole` بيسأل عن **الفريق الحالي**. جوه لوحة
 * مؤسسة الفريق الحالي هو المؤسسة، فالمدير العام كان هيبان «مالوش دور».
 * هنا السؤال صريح عن الفريق المركزي مهما كان السياق.
 *
 * الإجابة بتتخزّن لكل مستخدم — `Gate::before` بيتنادى مع كل قدرة لكل صف.
 * الذاكرة بتتمسح مع كل تبديل مستأجر (`TenantContextBootstrapper`) ومع أي
 * إسناد من هنا.
 */
final class CentralRoles implements CentralAuthority
{
    /** @var array<string, list<string>> */
    private array $names = [];

    public function __construct(private readonly TenantContext $context) {}

    /** @return list<string> */
    public static function catalog(): array
    {
        return array_map('strval', array_keys((array) config('authorization.central_roles', [])));
    }

    public static function isCentralRole(string $role): bool
    {
        return in_array($role, self::catalog(), true);
    }

    /** @return list<string> */
    public function names(Authenticatable $user): array
    {
        if (! $user instanceof Model) {
            return [];
        }

        return $this->names[$user->getMorphClass().':'.$user->getKey()] ??= $this->load($user);
    }

    public function has(Authenticatable $user, string $role): bool
    {
        return in_array($role, $this->names($user), true);
    }

    public function isSuperAdmin(Authenticatable $user): bool
    {
        return $this->has($user, (string) config('authorization.super_admin_role'));
    }

    public function isStaff(Authenticatable $user): bool
    {
        return $this->names($user) !== [];
    }

    /** @param list<string> $roles */
    public function sync(User $user, array $roles): void
    {
        $this->context->forTenant(null, static function () use ($user, $roles): void {
            $user->unsetRelation('roles');
            $user->syncRoles($roles);
        });

        $this->forget();
    }

    public function assign(User $user, string $role): void
    {
        $this->sync($user, array_values(array_unique([...$this->names($user), $role])));
    }

    public function forget(): void
    {
        $this->names = [];
    }

    /** @return list<string> */
    private function load(Model $user): array
    {
        $tables = (array) config('permission.table_names');
        $columns = (array) config('permission.column_names');

        return DB::connection((string) config('tenancy.database.central_connection'))
            ->table($tables['model_has_roles'].' as assigned')
            ->join($tables['roles'].' as role', 'role.id', '=', 'assigned.'.($columns['role_pivot_key'] ?? 'role_id'))
            ->where('assigned.'.$columns['team_foreign_key'], (int) config('authorization.central_team_id'))
            ->where('assigned.model_type', $user->getMorphClass())
            ->where('assigned.'.$columns['model_morph_key'], $user->getKey())
            ->pluck('role.name')
            ->map(static fn (mixed $name): string => (string) $name)
            ->values()
            ->all();
    }
}
