<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

/**
 * خطأ أعمال رسالته بتوصل للمستخدم زي ما هي (مترجمة من `billing::billing.errors.*`).
 */
final class BillingException extends DomainException
{
    /** @param array<string, string|int|float> $replace */
    public static function because(string $reason, array $replace = []): self
    {
        return new self(__("billing::billing.errors.{$reason}", $replace));
    }
}
