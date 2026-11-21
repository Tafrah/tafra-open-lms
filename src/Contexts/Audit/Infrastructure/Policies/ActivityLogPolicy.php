<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Audit\Domain\Models\ActivityLog;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Authorization\Policy;

/**
 * سجل التدقيق **مايتعدّلش ولا يتحذف من الواجهة** — دي قاعدة سلامة،
 * سجل تدقيق قابل للتعديل مالوش قيمة.
 */
final class ActivityLogPolicy extends Policy
{
    protected function resource(): string
    {
        return 'activity_logs';
    }

    /**
     * سجل التدقيق append-only — والقاعدة دي لازم تسري على المدير العام كمان.
     *
     * `create` كانت ناقصة من القائمة دي: التلات دوال بترجّع `deny` صراحةً،
     * بس `Gate::before` بيدّي المدير العام `true` لأي قدرة مش في
     * `invariants()` — يعني هو بالذات كان يقدر يضيف سجل تدقيق بإيده،
     * وهو الدور الوحيد اللي الحماية دي موجودة عشانه.
     */
    public function invariants(): array
    {
        return ['create', 'update', 'delete', 'forceDelete'];
    }

    /** سجل التدقيق شغل إداري — مش لكل من معاه `view_any.*` */
    public function area(): string
    {
        return 'access.system';
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->area($user, $this)->permission($user, $this, 'view_any')->response();
    }

    public function view(User $user, ActivityLog $log): Response
    {
        return $this->decide()
            ->area($user, $this)
            ->permission($user, $this, 'view')
            ->ruleOrNotFound($this->belongsToCurrentTenant($log), 'record_not_found')
            ->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->rule(false, 'audit.append_only')->response();
    }

    public function update(User $user, ActivityLog $log): Response
    {
        return $this->decide()->rule(false, 'audit.append_only')->response();
    }

    public function delete(User $user, ActivityLog $log): Response
    {
        return $this->decide()->rule(false, 'audit.append_only')->response();
    }

    public function prune(User $user): Response
    {
        return $this->decide()->area($user, $this)->permission($user, $this, 'prune')->response();
    }

    private function belongsToCurrentTenant(ActivityLog $log): bool
    {
        $current = app(TenantContext::class)->id();

        return $current === null || $log->tenantId() === null || $log->tenantId() === $current;
    }
}
