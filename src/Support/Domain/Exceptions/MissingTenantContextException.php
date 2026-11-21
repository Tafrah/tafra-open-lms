<?php

declare(strict_types=1);

namespace Src\Support\Domain\Exceptions;

/**
 * بترتمي لما استعلام على موديل تابع لمستأجر يتنفّذ من غير سياق مستأجر.
 *
 * الافتراضي = الرفض. من غير الاستثناء ده الاستعلام كان هيرجّع صفوف كل المستأجرين
 * — وده أخطر تسريب ممكن يحصل في التطبيق (docs/20-security.md بند ٣).
 */
final class MissingTenantContextException extends DomainException
{
    public function __construct(public readonly string $model)
    {
        parent::__construct("استعلام على [{$model}] بدون سياق مستأجر.");
    }
}
