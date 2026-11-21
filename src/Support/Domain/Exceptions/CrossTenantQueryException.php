<?php

declare(strict_types=1);

namespace Src\Support\Domain\Exceptions;

/**
 * `withoutScope()` على موديل تابع لمستأجر في وضع multi.
 *
 * في single، تجاوز الـ scope بيرجّع صفوف كل المستأجرين (للأوامر الإدارية).
 * في multi، الصفوف دي في قواعد مختلفة — استعلام واحد مايقدرش يوصلها.
 * الطريق الصح: `TenantContext::forEachTenant()`.
 */
final class CrossTenantQueryException extends \RuntimeException
{
    public function __construct(string $model)
    {
        parent::__construct(__('tenancy::tenancy.mode.cross_tenant_query', ['model' => $model]));
    }
}
