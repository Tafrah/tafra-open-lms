<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Infrastructure\Policies;

use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * سجل الاشتراكات.
 */
final class EnrollmentPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'enrollments';
    }
}
