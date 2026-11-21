<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Ai\Application\Services\AiAvailability;
use Src\Contexts\Ai\Domain\Models\AiConnection;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\Policy;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;

/**
 * تطبيقات الذكاء الاصطناعي المربوطة (docs/23 بند ٤).
 *
 * المستخدم **دايماً** يقدر يشوف ويلغي روابطه هو — حتى لو صلاحية `access.ai`
 * اتشالت منه بعد ما ربط: الإلغاء أمان، ومنعه بيسيب باب مفتوح.
 * روابط باقي أعضاء المؤسسة لمدير معاه `view_any`/`delete` في منطقة النظام.
 */
final class AiConnectionPolicy extends Policy
{
    protected function resource(): string
    {
        return 'ai_connections';
    }

    /**
     * المدير العام مايتجاوزش قواعد الربط: العضوية في المؤسسة ومفاتيح
     * الإيقاف بتسري عليه — من غيرها كان يربط AI على مؤسسة قافلة الوصول.
     */
    public function invariants(): array
    {
        return ['connect'];
    }

    /** روابط الكل منطقة نظام — المشاهد (`view_any.*`) مايوصلش لها. */
    public function area(): string
    {
        return 'access.system';
    }

    /**
     * ربط تطبيق جديد على مؤسسة (أو المركزية لو `null`).
     *
     * الصلاحية أول حاجة وبدون رسالة: اللي مالوش `access.ai` مالوش دعوة
     * يعرف إن الميزة موجودة. بعدها قواعد برسالة (المفاتيح، العضوية).
     */
    public function connect(User $user, ?Tenant $tenant = null): Response
    {
        $tenantId = $tenant?->getKey();

        if (! app(AiAvailability::class)->userMayConnect($user, $tenantId === null ? null : (int) $tenantId)) {
            return Response::deny();
        }

        return $this->decide()
            ->rule($user->is_active, 'ai.inactive_account')
            // منتحل بيربط AI باسم اللي بينتحله = وصول دايم بهويته بعد ما الانتحال
            // يخلص (`security.impersonation.max_minutes`). تصعيد مستتر — ممنوع.
            ->rule(! app(ImpersonationContext::class)->isActive(), 'while_impersonating')
            ->ruleUsing(
                fn (): bool => $tenantId === null
                    ? app(CentralRoles::class)->isStaff($user)
                    : $user->tenants()->whereKey($tenantId)->exists(),
                'ai.not_a_member',
            )
            ->ruleUsing(
                fn (): bool => $tenant === null || ($tenant->is_active && $tenant->suspended_at === null),
                'ai.tenant_unavailable',
            )
            ->ruleUsing(
                fn (): bool => app(AiAvailability::class)->enabledFor($tenantId === null ? null : (int) $tenantId),
                'ai.disabled',
            )
            ->response();
    }

    /** روابط كل أعضاء المؤسسة — مش روابطي أنا (دي من غير صلاحية). */
    public function viewAny(User $user): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'view_any')
            ->response();
    }

    public function view(User $user, AiConnection $connection): Response
    {
        if ($this->owns($user, $connection)) {
            return Response::allow();
        }

        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'view_any')
            ->ruleOrNotFound($this->inCurrentTenant($connection), 'record_not_found')
            ->response();
    }

    /** الإلغاء — بيقفل التوكن وتوكن التجديد فوراً. */
    public function delete(User $user, AiConnection $connection): Response
    {
        if ($this->owns($user, $connection)) {
            return $this->decide()->rule($connection->isActive(), 'ai.already_revoked')->response();
        }

        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'delete')
            ->ruleOrNotFound($this->inCurrentTenant($connection), 'record_not_found')
            ->rule($connection->isActive(), 'ai.already_revoked')
            ->response();
    }

    private function owns(User $user, AiConnection $connection): bool
    {
        return (int) $connection->user_id === (int) $user->getKey();
    }

    private function inCurrentTenant(AiConnection $connection): bool
    {
        $current = app(TenantContext::class)->id();

        return $current !== null && $connection->tenant_id === $current;
    }
}
