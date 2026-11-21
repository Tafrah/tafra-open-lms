<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Domain\Authorization\Policy;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;
use Src\Support\Infrastructure\Authorization\PolicyMemo;

/**
 * المؤسسات بتتدار من اللوحة المركزية بس (docs/24 بند ٣).
 *
 * مفيش شرط عضوية: فريق التشغيل بيشوف كل المؤسسات. الصلاحيات نفسها
 * على الفريق المركزي، فمدير مؤسسة مالوش أي منها حتى لو اسمها واحد.
 */
final class TenantPolicy extends Policy
{
    protected function resource(): string
    {
        return 'tenants';
    }

    /** كل قدرات المؤسسات محتاجة الدخول لمنطقة المؤسسات الأول */
    public function area(): string
    {
        return 'access.tenancy';
    }

    /**
     * قواعد سلامة مايتخطّاهاش حتى المدير العام: إيقاف أو حذف آخر مؤسسة
     * نشطة = قفل النظام، والدخول لمؤسسة لسه بتتجهّز = قاعدة ناقصة.
     */
    public function invariants(): array
    {
        return ['delete', 'forceDelete', 'deactivate', 'suspend', 'activate', 'impersonate'];
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->area($user, $this)
            ->permission($user, $this, 'view_any')->response();
    }

    public function view(User $user, Tenant $tenant): Response
    {
        return $this->decide()->area($user, $this)
            ->permission($user, $this, 'view')->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'create')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    public function update(User $user, Tenant $tenant): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'update')
            ->rule(! $tenant->trashed(), 'record_trashed')
            ->response();
    }

    public function delete(User $user, Tenant $tenant): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'delete')
            ->rule(! $tenant->trashed(), 'record_trashed')
            ->ruleUsing(fn (): bool => ! $this->isLastActive($tenant), 'tenancy.last_active_tenant')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    public function deactivate(User $user, Tenant $tenant): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'update')
            ->rule($tenant->is_active, 'tenancy.already_inactive')
            ->ruleUsing(fn (): bool => ! $this->isLastActive($tenant), 'tenancy.last_active_tenant')
            ->response();
    }

    public function suspend(User $user, Tenant $tenant): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'suspend')
            ->rule(! $tenant->trashed(), 'record_trashed')
            ->rule(! $tenant->isSuspended(), 'tenancy.already_suspended')
            ->ruleUsing(fn (): bool => ! $this->isLastActive($tenant), 'tenancy.last_active_tenant')
            ->response();
    }

    public function activate(User $user, Tenant $tenant): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'suspend')
            ->rule($tenant->isSuspended(), 'tenancy.not_suspended')
            ->response();
    }

    /** الانتحال مسموح على مؤسسة موقوفة — الدعم محتاج يشوف المشكلة (docs/24 بند ٤). */
    public function impersonate(User $user, Tenant $tenant): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'impersonate')
            ->rule(! $tenant->trashed(), 'record_trashed')
            ->rule($tenant->isProvisioned(), 'tenancy.not_provisioned')
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->response();
    }

    public function restore(User $user, Tenant $tenant): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'restore')
            ->rule($tenant->trashed(), 'record_not_trashed')
            ->response();
    }

    public function forceDelete(User $user, Tenant $tenant): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'force_delete')
            ->rule($tenant->trashed(), 'record_not_trashed')
            ->ruleUsing(fn (): bool => ! $this->isLastActive($tenant), 'tenancy.last_active_tenant')
            ->response();
    }

    public function export(User $user): Response
    {
        return $this->decide()->area($user, $this)
            ->permission($user, $this, 'export')->response();
    }

    /**
     * «فيه مؤسسة نشطة غيرها؟» — قايمة النشطين واحدة للصفحة كلها،
     * فبتتحسب مرة بدل استعلام لكل صف × كل إجراء.
     */
    private function isLastActive(Tenant $tenant): bool
    {
        /** @var list<int> $activeIds */
        $activeIds = app(PolicyMemo::class)->remember(
            'tenancy.active-ids',
            static fn (): array => Tenant::query()->active()->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
        );

        return array_values(array_diff($activeIds, [(int) $tenant->getKey()])) === [];
    }
}
