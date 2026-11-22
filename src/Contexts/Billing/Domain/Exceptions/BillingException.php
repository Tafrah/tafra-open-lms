<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Exceptions;

use Src\Support\Domain\Exceptions\DomainException;

/**
 * خطأ أعمال رسالته بتوصل للمستخدم زي ما هي (مترجمة من `billing::billing.errors.*`).
 * `reason` = المفتاح نفسه، عشان اللي بينادي يفرز (مثلًا التفعيل الجماعي بيعدّ «الرصيد مش كفاية» لوحده).
 */
final class BillingException extends DomainException
{
    public string $reason = '';

    /** @param array<string, string|int|float> $replace */
    public static function because(string $reason, array $replace = []): self
    {
        $exception = new self(__("billing::billing.errors.{$reason}", $replace));
        $exception->reason = $reason;

        return $exception;
    }
}
