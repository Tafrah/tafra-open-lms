<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserPreference;
use Src\Support\Domain\Authorization\Policy;

/** تفضيلات المستخدم بتاعته هو بس — مالهاش شاشة إدارة. */
final class UserPreferencePolicy extends Policy
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
        return $this->decide()->response();
    }

    public function view(User $user, UserPreference $preference): Response
    {
        return $this->decide()
            ->ruleOrNotFound($preference->user_id === $user->getKey(), 'record_not_found')
            ->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->response();
    }

    public function update(User $user, UserPreference $preference): Response
    {
        return $this->decide()
            ->ruleOrNotFound($preference->user_id === $user->getKey(), 'record_not_found')
            ->response();
    }

    public function delete(User $user, UserPreference $preference): Response
    {
        return $this->decide()
            ->ruleOrNotFound($preference->user_id === $user->getKey(), 'record_not_found')
            ->response();
    }
}
