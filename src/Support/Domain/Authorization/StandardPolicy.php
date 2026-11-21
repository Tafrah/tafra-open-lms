<?php

declare(strict_types=1);

namespace Src\Support\Domain\Authorization;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Application\Contracts\TenantContext;

/**
 * القدرات القياسية لمورد مملوك لمؤسسة — صلاحية + «السجل من مؤسستي» + قواعد الحذف الناعم.
 *
 * موارد المنصة التعليمية كتير وأغلبها CRUD عادي؛ الكلاس ده بيمنع تكرار
 * نفس ٨ دوال في كل Policy. أي قاعدة أعمال زيادة بتتكتب في الـ Policy نفسها
 * عن طريق `extraRules()` أو بإعادة تعريف الدالة.
 */
abstract class StandardPolicy extends Policy
{
    public function viewAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'view_any')->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'create')->response();
    }

    public function export(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'export')->response();
    }

    public function deleteAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'delete_any')->response();
    }

    public function view(User $user, Model $record): Response
    {
        return $this->owned($user, $record, 'view')->response();
    }

    public function update(User $user, Model $record): Response
    {
        return $this->owned($user, $record, 'update')
            ->rule(! $this->isTrashed($record), 'record_trashed')
            ->response();
    }

    public function delete(User $user, Model $record): Response
    {
        return $this->owned($user, $record, 'delete')
            ->rule(! $this->isTrashed($record), 'record_trashed')
            ->response();
    }

    public function restore(User $user, Model $record): Response
    {
        return $this->owned($user, $record, 'restore')
            ->rule($this->isTrashed($record), 'record_not_trashed')
            ->response();
    }

    public function forceDelete(User $user, Model $record): Response
    {
        return $this->owned($user, $record, 'force_delete')
            ->rule($this->isTrashed($record), 'record_not_trashed')
            ->response();
    }

    /** صلاحية + السجل تابع للمؤسسة الحالية (من مؤسسة تانية = «مش موجود») */
    protected function owned(User $user, Model $record, string $action): Decision
    {
        return $this->decide()
            ->permission($user, $this, $action)
            ->ruleOrNotFound(
                (int) $record->getAttribute('tenant_id') === (int) app(TenantContext::class)->id(),
                'record_not_found',
            );
    }

    protected function isTrashed(Model $record): bool
    {
        return method_exists($record, 'trashed') && $record->trashed();
    }
}
