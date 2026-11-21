<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LessonType: string implements HasColor, HasIcon, HasLabel
{
    case YoutubeVideo = 'YoutubeVideo';
    case Pdf = 'Pdf';
    case Text = 'Text';

    public function getLabel(): string
    {
        return __("academy::academy.enums.lesson_type.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::YoutubeVideo => 'danger',
            self::Pdf => 'warning',
            self::Text => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::YoutubeVideo => 'heroicon-o-play-circle',
            self::Pdf => 'heroicon-o-document-text',
            self::Text => 'heroicon-o-bars-3-bottom-right',
        };
    }
}
