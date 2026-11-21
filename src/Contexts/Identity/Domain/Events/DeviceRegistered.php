<?php

declare(strict_types=1);

namespace Src\Contexts\Identity\Domain\Events;

use Src\Support\Domain\Events\DomainEvent;

final readonly class DeviceRegistered extends DomainEvent
{
    public function __construct(
        public int $userId,
        public int $deviceId,
        public string $deviceName,
        public string $ipAddress,
        ?int $tenantId = null,
    ) {
        parent::__construct($tenantId);
    }
}
