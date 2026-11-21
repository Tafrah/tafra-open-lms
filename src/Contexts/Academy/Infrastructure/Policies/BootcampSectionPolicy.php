<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * أجزاء الكورس — بتتبع صلاحيات الكورس نفسه.
 */
final class BootcampSectionPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'bootcamps';
    }
}
