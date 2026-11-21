<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * تقدّم الطلبة — بيتعرض من شاشة الطالب.
 */
final class SectionItemViewPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'students';
    }
}
