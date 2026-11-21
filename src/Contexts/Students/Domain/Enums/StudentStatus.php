<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StudentStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Blocked = 'blocked';

    public function getLabel(): string
    {
        return __("students::students.enums.student_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Blocked => 'danger',
        };
    }
}
