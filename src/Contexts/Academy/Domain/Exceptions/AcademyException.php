<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

/**
 * خطأ أعمال رسالته بتوصل للمستخدم زي ما هي (مترجمة من `academy::academy.errors.*`).
 */
final class AcademyException extends DomainException
{
    /** @param array<string, string|int|float> $replace */
    public static function because(string $reason, array $replace = []): self
    {
        return new self(__("academy::academy.errors.{$reason}", $replace));
    }
}
