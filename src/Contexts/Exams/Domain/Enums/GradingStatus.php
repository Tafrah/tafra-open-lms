<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GradingStatus: string implements HasColor, HasLabel
{
    case Auto = 'auto';
    case Pending = 'pending';
    case Graded = 'graded';

    public function getLabel(): string
    {
        return __("exams::exams.enums.grading_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Auto => 'gray',
            self::Pending => 'warning',
            self::Graded => 'success',
        };
    }
}
