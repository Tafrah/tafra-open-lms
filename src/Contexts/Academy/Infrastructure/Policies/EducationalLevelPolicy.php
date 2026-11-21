<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * الصفوف الدراسية.
 */
final class EducationalLevelPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'educational_levels';
    }
}
