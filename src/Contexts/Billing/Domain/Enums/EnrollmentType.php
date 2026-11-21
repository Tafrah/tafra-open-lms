<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnrollmentType: string implements HasColor, HasLabel
{
    case Enroll = 'enroll';
    case Unenroll = 'unenroll';

    public function getLabel(): string
    {
        return __("billing::billing.enums.enrollment_type.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Enroll => 'success',
            self::Unenroll => 'danger',
        };
    }
}
