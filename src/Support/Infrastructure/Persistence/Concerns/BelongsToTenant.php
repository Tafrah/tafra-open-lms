<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Persistence\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Domain\Exceptions\MissingTenantContextException;
use Src\Support\Infrastructure\Persistence\Scopes\TenantScope;

/**
 * أي موديل تابع لمستأجر لازم يستخدم الـ trait ده.
 *
 * فيه اختبار بيمرّ على كل الموديلات ويفشل لو موديل فيه عمود tenant_id
 * من غير الـ trait — وده أخطر باگ ممكن يحصل (docs/03 بند ٥).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                throw new MissingTenantContextException(static::class);
            }

            $model->setAttribute('tenant_id', $tenantId);
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /*
     * ملاحظة: مفيش هنا مساعد لتجاوز الـ scope عن قصد.
     * الطريق الوحيد الموثّق هو `TenantContext::withoutScope()` —
     * مكان واحد، صريح، ومُختبَر (docs/20 بند ٣).
     */

}
