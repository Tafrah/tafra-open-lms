<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Authorization;

use Illuminate\Support\Facades\Gate;
use Src\Support\Domain\Authorization\Policy;

/**
 * بيجاوب على سؤال واحد: هل القدرة دي محمية بقاعدة سلامة
 * لا يتجاوزها المدير العام؟ (docs/19-policies.md بند ٥)
 */
final class InvariantRegistry
{
    public function guards(string $ability, mixed $argument): bool
    {
        if ($argument === null) {
            return false;
        }

        $policy = Gate::getPolicyFor($argument);

        return $policy instanceof Policy && $policy->isInvariant($ability);
    }
}
