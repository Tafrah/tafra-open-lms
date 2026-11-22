<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * استثناءات المشاهدات — بتتدار من شاشة الطالب (صلاحية `grant_views.students`).
 */
final class StudentItemViewGrantPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'students';
    }
}
