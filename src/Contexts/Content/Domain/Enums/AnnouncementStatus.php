<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return __("content::content.announcement.status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Archived => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Published => 'heroicon-o-check-circle',
            self::Archived => 'heroicon-o-archive-box',
        };
    }
}
