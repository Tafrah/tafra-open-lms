<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

/**
 * خطأ رسالته بتوصل للطالب زي ما هي (مترجمة من `storefront::storefront.errors.*`).
 */
final class StorefrontException extends DomainException
{
    /** @param array<string, string|int|float> $replace */
    public static function because(string $reason, array $replace = []): self
    {
        return new self(__("storefront::storefront.errors.{$reason}", $replace));
    }
}
