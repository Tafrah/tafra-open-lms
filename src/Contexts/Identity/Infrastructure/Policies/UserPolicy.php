<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Enums\UserStatus;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\Policy;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;
use Src\Support\Infrastructure\Authorization\PolicyMemo;

final class UserPolicy extends Policy
{
    /** @var list<string> */
    private const ADMIN_ROLES = ['admin'];

    protected function resource(): string
    {
        return 'users';
    }

    /**
     * قواعد سلامة لا يتجاوزها المدير العام.
     * من غيرها يقدر يحذف نفسه ويقفل على نفسه بره النظام.
     */
    public function invariants(): array
    {
        return ['delete', 'forceDelete', 'impersonate', 'manageRoles', 'forceLogout', 'resetPassword'];
    }

    // ══════════════ قدرات بدون سجل ══════════════

    public function viewAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'view_any')->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()
            ->permission($user, $this, 'create')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    public function export(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'export')->response();
    }

    // ══════════════ قدرات على سجل ══════════════

    public function view(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'view')
            // سجل من مؤسسة تانية: 404 مش 403 — «ممنوع» بتأكد إنه موجود
            ->ruleOrNotFound($this->sharesTenant($target), 'record_not_found')
            ->response();
    }

    public function update(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'update')
            ->ruleOrNotFound($this->sharesTenant($target), 'record_not_found')
            ->rule(! $target->trashed(), 'record_trashed')
            // مدير عام مايتعدّلش إلا بمدير عام
            ->rule($this->mayTouchSuperAdmin($user, $target), 'user.super_admin_only')
            ->response();
    }

    public function delete(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'delete')
            ->ruleOrNotFound($this->sharesTenant($target), 'record_not_found')
            ->rule($user->isNot($target), 'self_target')
            ->rule(! $target->trashed(), 'record_trashed')
            ->rule($this->mayTouchSuperAdmin($user, $target), 'user.super_admin_only')
            ->ruleUsing(fn (): bool => ! $this->isLastAdmin($target), 'last_admin')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    public function restore(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'restore')
            ->rule($target->trashed(), 'record_not_trashed')
            ->response();
    }

    public function forceDelete(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'force_delete')
            ->rule($user->isNot($target), 'self_target')
            ->rule($target->trashed(), 'record_not_trashed')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    // ══════════════ قدرات مخصصة ══════════════

    public function impersonate(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'impersonate')
            ->rule($user->isNot($target), 'self_target')
            ->rule(! $target->trashed(), 'record_trashed')
            ->rule($target->is_active && $target->status === UserStatus::Active, 'user.inactive_target')
            // انتحال مدير عام ممنوع — حتى على مدير عام تاني
            ->rule(! $this->isSuperAdmin($target), 'user.cannot_impersonate_super_admin')
            // فريق التشغيل (support) معاه دخول /central — انتحاله من مؤسسة تصعيد صلاحيات
            ->rule(! app(CentralRoles::class)->isStaff($target), 'user.cannot_impersonate_staff')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    public function resetPassword(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'reset_password')
            ->ruleOrNotFound($this->sharesTenant($target), 'record_not_found')
            ->rule(! $target->trashed(), 'record_trashed')
            ->rule($this->mayTouchSuperAdmin($user, $target), 'user.super_admin_only')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    public function forceLogout(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'force_logout')
            ->ruleOrNotFound($this->sharesTenant($target), 'record_not_found')
            ->rule($user->isNot($target), 'self_target')
            ->rule($this->mayTouchSuperAdmin($user, $target), 'user.super_admin_only')
            ->response();
    }

    public function manageRoles(User $user, User $target): Response
    {
        return $this->decide()
            ->permission($user, $this, 'manage_roles')
            ->ruleOrNotFound($this->sharesTenant($target), 'record_not_found')
            // مايقدرش يرفع صلاحيات نفسه
            ->rule($user->isNot($target), 'self_target')
            ->rule($this->mayTouchSuperAdmin($user, $target), 'user.super_admin_only')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    // ══════════════ مساعدات ══════════════

    private function sharesTenant(User $target): bool
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            return true;
        }

        // المورد بيحمّل `tenants` مع الاستعلام — لو محمّلة، مفيش داعي
        // نضرب القاعدة تاني لكل صف.
        if ($target->relationLoaded('tenants')) {
            return $target->getRelation('tenants')
                ->contains(static fn (object $tenant): bool => $tenant->getKey() === $tenantId);
        }

        return app(PolicyMemo::class)->remember(
            "user.shares-tenant:{$tenantId}:{$target->getKey()}",
            static fn (): bool => $target->tenants()->whereKey($tenantId)->exists(),
        );
    }

    /**
     * القاعدة «مدير عام مايتلمسش إلا من مدير عام» قاعدة أعمال مش صلاحية.
     * المدير العام دوره على الفريق المركزي — مش على المؤسسة الحالية (docs/24 بند ٢).
     */
    private function isSuperAdmin(User $target): bool
    {
        return app(CentralRoles::class)->isSuperAdmin($target);
    }

    private function mayTouchSuperAdmin(User $actor, User $target): bool
    {
        return ! $this->isSuperAdmin($target) || $this->isSuperAdmin($actor);
    }

    /**
     * آخر مدير في المؤسسة مايتشالش — وإلا المؤسسة تقفل على نفسها.
     *
     * قائمة المديرين واحدة للصفحة كلها، فبتتحسب مرة وبتتخزّن —
     * بدل استعلام لكل صف في الجدول.
     */
    private function isLastAdmin(User $target): bool
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            return false;
        }

        if ($target->getRoleNames()->intersect(self::ADMIN_ROLES)->isEmpty()) {
            return false;
        }

        $adminIds = app(PolicyMemo::class)->remember(
            "tenant.admin-ids:{$tenantId}",
            static fn (): array => User::query()
                ->ofTenant($tenantId)
                ->whereHas('roles', static fn ($query) => $query->whereIn('name', self::ADMIN_ROLES))
                ->pluck('id')
                ->all(),
        );

        return array_values(array_diff($adminIds, [$target->getKey()])) === [];
    }
}
