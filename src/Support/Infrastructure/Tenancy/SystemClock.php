<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Tenancy;

use Carbon\CarbonImmutable;
use Src\Support\Application\Contracts\Clock;

final class SystemClock implements Clock
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now();
    }
}
