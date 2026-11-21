<?php

declare(strict_types=1);

namespace Src\Support\Application\Contracts;

/** المصدر الوحيد لمعرفة المستأجر الحالي من أي طبقة. */
interface TenantContext
{
    public function id(): ?int;

    public function set(?int $tenantId): void;

    /** هل تجاوز الـ scope مفعّل حالياً؟ (أوامر إدارية فقط) */
    public function isBypassed(): bool;

    /** للأوامر والتقارير عبر كل المستأجرين — استخدمه بحذر شديد */
    public function withoutScope(callable $callback): mixed;

    /** ينفّذ الـ callback مرة لكل مستأجر نشط، بالسياق مضبوط */
    public function forEachTenant(callable $callback): void;

    /** ينفّذ الـ callback بسياق مستأجر محدد ثم يرجّع السياق السابق */
    public function forTenant(?int $tenantId, callable $callback): mixed;
}
