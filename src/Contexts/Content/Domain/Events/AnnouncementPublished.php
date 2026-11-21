<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Domain\Events;

use Src\Support\Domain\Events\DomainEvent;

final readonly class AnnouncementPublished extends DomainEvent
{
    public function __construct(
        public int $announcementId,
        int $tenantId,
    ) {
        parent::__construct($tenantId);
    }
}
