<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

/**
 * خطأ أعمال رسالته بتوصل للمستخدم زي ما هي (مترجمة من `exams::exams.errors.*`).
 */
final class ExamsException extends DomainException
{
    /** @param array<string, string|int|float> $replace */
    public static function because(string $reason, array $replace = []): self
    {
        return new self(__("exams::exams.errors.{$reason}", $replace));
    }
}
