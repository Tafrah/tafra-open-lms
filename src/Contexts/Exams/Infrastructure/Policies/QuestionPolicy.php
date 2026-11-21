<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * أسئلة الامتحان — بتتبع صلاحيات الامتحان.
 */
final class QuestionPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'online_exams';
    }
}
