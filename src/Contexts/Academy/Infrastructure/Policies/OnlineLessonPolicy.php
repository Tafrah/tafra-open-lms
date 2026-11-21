<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * مكتبة الدروس.
 */
final class OnlineLessonPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'online_lessons';
    }
}
