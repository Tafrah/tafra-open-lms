<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Application\DataObjects;

use Carbon\CarbonImmutable;

final readonly class PublishAnnouncementData
{
    public function __construct(
        public int $announcementId,
        public ?CarbonImmutable $publishAt = null,
        public bool $notifyUsers = true,
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            announcementId: (int) $data['id'],
            publishAt: isset($data['publish_at']) && filled($data['publish_at'])
                ? CarbonImmutable::parse($data['publish_at'])
                : null,
            notifyUsers: (bool) ($data['notify_users'] ?? true),
        );
    }
}
