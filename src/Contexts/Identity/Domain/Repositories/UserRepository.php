<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Domain\Repositories;

use Illuminate\Support\Collection;
use Src\Contexts\Identity\Application\DataObjects\InviteUserData;
use Src\Contexts\Identity\Domain\Models\User;

interface UserRepository
{
    public function find(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(InviteUserData $data): User;

    /** @return Collection<int, User> */
    public function activeInTenant(int $tenantId): Collection;

    public function nextCode(): string;
}
