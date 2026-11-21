<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Notifications\Domain\Models\NotificationPreference;
use Src\Support\Domain\Authorization\Policy;

/** تفضيلات الإشعارات شخصية — صاحبها بس. */
final class NotificationPreferencePolicy extends Policy
{
    protected function resource(): string
    {
        return 'settings';
    }

    public function invariants(): array
    {
        return ['viewAny', 'view', 'create', 'update', 'delete'];
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->response();
    }

    public function view(User $user, NotificationPreference $preference): Response
    {
        return $this->decide()
            ->ruleOrNotFound($preference->user_id === $user->getKey(), 'record_not_found')
            ->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->response();
    }

    public function update(User $user, NotificationPreference $preference): Response
    {
        return $this->decide()
            ->ruleOrNotFound($preference->user_id === $user->getKey(), 'record_not_found')
            ->response();
    }

    public function delete(User $user, NotificationPreference $preference): Response
    {
        return $this->decide()
            ->ruleOrNotFound($preference->user_id === $user->getKey(), 'record_not_found')
            ->response();
    }
}
