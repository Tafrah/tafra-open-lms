<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AnnouncementSeverity: string implements HasColor, HasIcon, HasLabel
{
    case Info = 'info';
    case Warning = 'warning';
    case Urgent = 'urgent';

    public function getLabel(): string
    {
        return __("content::content.announcement.severity.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Info => 'info',
            self::Warning => 'warning',
            self::Urgent => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Info => 'heroicon-o-information-circle',
            self::Warning => 'heroicon-o-exclamation-triangle',
            self::Urgent => 'heroicon-o-bell-alert',
        };
    }
}
