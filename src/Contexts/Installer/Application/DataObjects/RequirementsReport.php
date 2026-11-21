<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Application\DataObjects;

final readonly class RequirementsReport
{
    /** @param list<RequirementCheck> $checks */
    public function __construct(public array $checks) {}

    public function passes(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->blocking && ! $check->passed) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, list<RequirementCheck>> */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->checks as $check) {
            $groups[$check->group][] = $check;
        }

        return $groups;
    }

    /** @return list<RequirementCheck> */
    public function failures(): array
    {
        return array_values(array_filter($this->checks, static fn (RequirementCheck $check): bool => $check->blocking && ! $check->passed));
    }
}
