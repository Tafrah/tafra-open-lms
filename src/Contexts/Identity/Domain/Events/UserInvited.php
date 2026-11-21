<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Domain\Events;

use Src\Support\Domain\Events\DomainEvent;

final readonly class UserInvited extends DomainEvent
{
    public function __construct(
        public int $userId,
        ?int $tenantId = null,
        public ?string $inviteUrl = null,
    ) {
        parent::__construct($tenantId);
    }
}
