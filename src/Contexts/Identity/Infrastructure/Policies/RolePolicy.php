<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\Policy;

final class RolePolicy extends Policy
{
    protected function resource(): string
    {
        return 'roles';
    }

    /**
     * إدارة الأدوار شغل إداري — نمط `view_any.*` في دور المشاهد كان
     * بيوصّله لشاشة الأدوار بالرابط المباشر. المنطقة بتقفلها
     * (docs/21 بند ٣).
     */
    public function area(): string
    {
        return 'access.system';
    }

    /** الأدوار المحمية مايتحذفوش ولا يتعدّلوا — حتى من المدير العام. */
    public function invariants(): array
    {
        return ['update', 'delete'];
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->area($user, $this)->permission($user, $this, 'view_any')->response();
    }

    public function view(User $user, Role $role): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'view')
            ->ruleOrNotFound($this->visibleHere($role), 'role.other_tenant')
            ->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->area($user, $this)->permission($user, $this, 'create')->response();
    }

    public function update(User $user, Role $role): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'update')
            ->ruleOrNotFound($this->visibleHere($role), 'role.other_tenant')
            ->rule(! $role->isProtected(), 'role.protected')
            ->response();
    }

    public function delete(User $user, Role $role): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'delete')
            ->ruleOrNotFound($this->visibleHere($role), 'role.other_tenant')
            ->rule(! $role->isProtected(), 'role.protected')
            ->ruleUsing(
                fn (): bool => ! $role->users()->exists(),
                'role.still_assigned',
            )
            ->response();
    }

    /** دور مخصص لمؤسسة تانية = مش موجود بالنسبة لك (404 مش 403). */
    private function visibleHere(Role $role): bool
    {
        $tenantId = $role->getAttribute('tenant_id');

        return $tenantId === null || (int) $tenantId === app(TenantContext::class)->id();
    }
}
