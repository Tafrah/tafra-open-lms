<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

/**
 * خطأ أعمال رسالته بتوصل للمستخدم زي ما هي (مترجمة من `students::students.errors.*`).
 */
final class StudentsException extends DomainException
{
    /** @param array<string, string|int|float> $replace */
    public static function because(string $reason, array $replace = []): self
    {
        return new self(__("students::students.errors.{$reason}", $replace));
    }
}
