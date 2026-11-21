<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\DataObjects;

use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

final readonly class AcademyOwner
{
    public function __construct(
        public Tenant $tenant,
        public User $user,
    ) {}
}
