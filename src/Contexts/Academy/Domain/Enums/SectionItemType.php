<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SectionItemType: string implements HasColor, HasIcon, HasLabel
{
    case Lesson = 'lesson';
    case Exam = 'exam';

    public function getLabel(): string
    {
        return __("academy::academy.enums.section_item_type.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Lesson => 'primary',
            self::Exam => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Lesson => 'heroicon-o-play-circle',
            self::Exam => 'heroicon-o-clipboard-document-check',
        };
    }
}
