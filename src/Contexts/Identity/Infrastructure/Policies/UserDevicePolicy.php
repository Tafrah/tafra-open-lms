<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Domain\Models\UserDevice;
use Src\Support\Domain\Authorization\Policy;

/**
 * الأجهزة بيانات شخصية — صاحبها بس هو اللي بيشوفها ويقدر ينهيها.
 * حتى المدير العام مايشوفش أجهزة غيره (قاعدة سلامة، مش صلاحية).
 */
final class UserDevicePolicy extends Policy
{
    protected function resource(): string
    {
        return 'users';
    }

    public function invariants(): array
    {
        return ['view', 'viewAny', 'delete', 'update', 'create'];
    }

    public function viewAny(User $user): Response
    {
        return $this->decide()->response();
    }

    public function view(User $user, UserDevice $device): Response
    {
        return $this->decide()
            ->ruleOrNotFound($this->owns($user, $device), 'record_not_found')
            ->response();
    }

    public function create(User $user): Response
    {
        return $this->decide()->rule(false, 'self_target')->response();
    }

    public function update(User $user, UserDevice $device): Response
    {
        return $this->decide()->rule(false, 'self_target')->response();
    }

    public function delete(User $user, UserDevice $device): Response
    {
        return $this->decide()
            ->ruleOrNotFound($this->owns($user, $device), 'record_not_found')
            ->response();
    }

    private function owns(User $user, UserDevice $device): bool
    {
        return $device->user_id === $user->getKey();
    }
}
