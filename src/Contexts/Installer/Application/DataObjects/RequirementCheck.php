<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\DataObjects;

/** بند واحد في فحص المتطلبات. `blocking = false` يعني تحذير بس. */
final readonly class RequirementCheck
{
    public function __construct(
        public string $group,
        public string $label,
        public bool $passed,
        public bool $blocking = true,
        public string $current = '',
        public string $hint = '',
    ) {}
}
