<?php

declare(strict_types=1);

namespace Src\Support\Domain\Exceptions;

/**
 * الوضع في `.env` مختلف عن الوضع اللي اتقفل عند أول نشر.
 */
final class TenancyModeMismatchException extends \RuntimeException
{
    public function __construct(public readonly string $locked, public readonly string $configured)
    {
        parent::__construct(__('tenancy::tenancy.mode.mismatch', [
            'locked' => $locked,
            'configured' => $configured,
        ]));
    }
}
