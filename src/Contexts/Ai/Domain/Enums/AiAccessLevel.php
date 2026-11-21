<?php

declare(strict_types=1);

namespace Src\Contexts\Ai\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * مستوى الربط اللي المستخدم اختاره في شاشة الموافقة.
 *
 * مش بديل عن الصلاحيات: «كتابة» معناها إن أدوات الكتابة **ممكن** تظهر —
 * والـ Policy بعدها بتقرر لكل أداة ولكل سجل زي اللوحة بالظبط.
 */
enum AiAccessLevel: string implements HasColor, HasDescription, HasLabel
{
    case Read = 'read';
    case Write = 'write';

    public function getLabel(): string
    {
        return __("ai::ai.access.{$this->value}.label");
    }

    public function getDescription(): string
    {
        return __("ai::ai.access.{$this->value}.description");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Read => 'gray',
            self::Write => 'warning',
        };
    }

    public function allowsWrites(): bool
    {
        return $this === self::Write;
    }
}
