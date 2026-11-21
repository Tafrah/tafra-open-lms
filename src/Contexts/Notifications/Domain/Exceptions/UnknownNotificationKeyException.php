<?php

declare(strict_types=1);

namespace Src\Contexts\Notifications\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

final class UnknownNotificationKeyException extends DomainException
{
    public function __construct(public readonly string $key)
    {
        parent::__construct("إشعار [{$key}] مش موجود في كتالوج config/notifications.php.");
    }
}
