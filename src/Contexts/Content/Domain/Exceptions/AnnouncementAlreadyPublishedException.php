<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

final class AnnouncementAlreadyPublishedException extends DomainException
{
    public function __construct(public readonly int $announcementId)
    {
        parent::__construct(__('content::content.announcement.errors.already_published'));
    }
}
