<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\Permission;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\Policy;

/**
 * الصلاحيات مصدرها `config/authorization.php` — مفيش إنشاء أو تعديل
 * من الواجهة، فالـ Policy بترفض كل حاجة ما عدا القراءة.
 */
final class PermissionPolicy extends Policy
{
    protected function resource(): string
    {
        return 'roles';
    }

    public function invariants(): array
    {
        return ['create', 'update', 'delete'];
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'view_any')->response();
    }

    public function view(User $user, Permission $permission): Response
    {
        return $this->decide()->permission($user, $this, 'view')->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->rule(false, 'permission.config_owned')->response();
    }

    public function update(User $user, Permission $permission): Response
    {
        return $this->decide()->rule(false, 'permission.config_owned')->response();
    }

    public function delete(User $user, Permission $permission): Response
    {
        return $this->decide()->rule(false, 'permission.config_owned')->response();
    }
}
