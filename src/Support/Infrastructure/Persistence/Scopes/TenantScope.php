<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Persistence\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Exceptions\CrossTenantQueryException;
use Src\Support\Domain\Exceptions\MissingTenantContextException;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/**
 * الطبقة ٢ من عزل المستأجرين — الحماية الحقيقية.
 *
 * الافتراضي = الرفض: استعلام من غير سياق بيرمي استثناء،
 * مش بيرجّع صفوف كل المستأجرين.
 *
 * في وضع multi الـ scope بيفضل شغّال فوق فصل القواعد — دفاع إضافي،
 * ونفس اختبارات العزل بتعدّي في الوضعين (docs/03 بند ٣).
 *
 * @implements Scope<Model>
 */
final class TenantScope implements Scope
{
    /** @param  Builder<covariant Model>  $builder */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        $tenantId = $context->id();

        if ($tenantId === null) {
            if (! $context->isBypassed()) {
                throw new MissingTenantContextException($model::class);
            }

            // صفوف كل المستأجرين في قواعد مختلفة — استعلام واحد مايوصلهاش
            if (TenancyMode::isMulti()) {
                throw new CrossTenantQueryException($model::class);
            }

            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
    }
}
