<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * بيانات السؤال المقالي — بتتبع صلاحيات الامتحان.
 */
final class EssayQuestionPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'online_exams';
    }
}
