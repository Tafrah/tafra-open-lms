<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuestionType: string implements HasColor, HasLabel
{
    case MultipleChoice = 'multiple_choice';
    case Essay = 'essay';

    public function getLabel(): string
    {
        return __("exams::exams.enums.question_type.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MultipleChoice => 'primary',
            self::Essay => 'warning',
        };
    }
}
