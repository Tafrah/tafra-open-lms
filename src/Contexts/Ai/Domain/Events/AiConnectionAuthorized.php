<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Domain\Events;

use Src\Support\Domain\Events\DomainEvent;

final readonly class AiConnectionAuthorized extends DomainEvent
{
    public function __construct(
        public int $connectionId,
        public int $userId,
        ?int $tenantId = null,
    ) {
        parent::__construct($tenantId);
    }
}
