<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserPasswordHistory;
use Src\Support\Domain\Authorization\Policy;

/**
 * تاريخ كلمات المرور **مايتقراش من أي حد** — هو بس أداة لمنع
 * إعادة الاستخدام. الرفض هنا مطلق ومقصود.
 */
final class UserPasswordHistoryPolicy extends Policy
{
    protected function resource(): string
    {
        return 'users';
    }

    public function invariants(): array
    {
        return ['viewAny', 'view', 'create', 'update', 'delete'];
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->rule(false, 'record_not_found')->response();
    }

    public function view(User $user, UserPasswordHistory $history): Response
    {
        return $this->decide()->ruleOrNotFound(false, 'record_not_found')->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->rule(false, 'record_not_found')->response();
    }

    public function update(User $user, UserPasswordHistory $history): Response
    {
        return $this->decide()->ruleOrNotFound(false, 'record_not_found')->response();
    }

    public function delete(User $user, UserPasswordHistory $history): Response
    {
        return $this->decide()->ruleOrNotFound(false, 'record_not_found')->response();
    }
}
