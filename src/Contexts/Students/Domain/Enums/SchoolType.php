<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum SchoolType: string implements HasLabel
{
    case General = 'general';
    case Azhar = 'azhar';

    public function getLabel(): string
    {
        return __("students::students.enums.school_type.{$this->value}");
    }
}
