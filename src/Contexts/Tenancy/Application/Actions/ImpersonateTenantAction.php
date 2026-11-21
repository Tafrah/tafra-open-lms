<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Application\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;
use Src\Support\Infrastructure\Authorization\ImpersonationContext;

/**
 * فريق التشغيل بيدخل لوحة المؤسسة بالانتحال بس — مش بالعضوية (docs/24 بند ٢).
 *
 * بيدخل بصفة **أقدم مدير نشط** في المؤسسة. قيود الانتحال العادية سارية:
 * العمليات الحساسة مقفولة، والجلسة بتنتهي بعد المدة القصوى وبتتسجّل.
 */
final readonly class ImpersonateTenantAction
{
    public function __construct(
        private TenantContext $context,
        private ImpersonationContext $impersonation,
    ) {}

    public function handle(Tenant $tenant, User $actor): User
    {
        Gate::forUser($actor)->authorize('impersonate', $tenant);

        $tenantId = (int) $tenant->getKey();

        // الأدوار مربوطة بفريق المؤسسة — السؤال لازم يتسأل جوه سياقها
        $target = $this->context->forTenant($tenantId, static fn (): ?User => User::query()
            ->ofTenant($tenantId)
            ->active()
            ->whereHas('roles', static fn (Builder $query): Builder => $query
                ->where('name', (string) config('authorization.tenant_admin_role')))
            ->orderBy('id')
            ->first());

        if (! $target instanceof User) {
            throw ValidationException::withMessages([
                'tenant' => __('authorization.denied.tenancy.no_admin_to_impersonate'),
            ]);
        }

        // فريق التشغيل مش عضو في أي مؤسسة — الرجوع لازم يكون للوحة المركزية،
        // وإلا «إنهاء الانتحال» بيوديه `/admin` من غير مؤسسات = طريق مسدود
        $this->impersonation->enter($actor, $target, backTo: (string) config('tenancy.central_panel_url', '/central'));

        activity('security')
            ->causedBy($actor)
            ->performedOn($target)
            ->withProperties(['tenant_id' => $tenantId])
            ->log('tenant_impersonated');

        return $target;
    }
}
