<?php

declare(strict_types=1);

namespace Src\Contexts\Transfer\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

final class TransferException extends DomainException
{
    /** @param array<string, string|int|float> $replace */
    public static function because(string $reason, array $replace = []): self
    {
        return new self(__("transfer::transfer.errors.{$reason}", $replace));
    }
}
