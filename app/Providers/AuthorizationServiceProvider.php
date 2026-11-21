<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Src\Support\Domain\Authorization\CentralAuthority;
use Src\Support\Infrastructure\Authorization\CentralRoles;
use Src\Support\Infrastructure\Authorization\InvariantRegistry;
use Src\Support\Infrastructure\Authorization\PermissionBuilder;

/**
 * المكان الثاني والأخير المسموح فيه فحص صلاحية مباشر
 * (الأول هو Src\Support\Domain\Authorization\Decision).
 *
 * راجع docs/19-policies.md بند ٥ و٦ و٧.
 */
final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionBuilder::class);
        $this->app->singleton(InvariantRegistry::class);

        // scoped: ذاكرة الأدوار المركزية لكل طلب/Job — مش لعمر العامل كله
        $this->app->scoped(CentralRoles::class);
        $this->app->alias(CentralRoles::class, CentralAuthority::class);
    }

    public function boot(): void
    {
        $this->discoverPolicies();
        $this->registerSuperAdminBypass();
        $this->registerAbilityGates();
    }

    /**
     * الموديلات بتاعتنا مش في App\Models، فالاكتشاف التلقائي مش هيلاقيها.
     * Src\Contexts\X\Domain\Models\Y → Src\Contexts\X\Infrastructure\Policies\YPolicy
     */
    private function discoverPolicies(): void
    {
        Gate::guessPolicyNamesUsing(
            static fn (string $model): string => Str::of($model)
                ->replace('\\Domain\\Models\\', '\\Infrastructure\\Policies\\')
                ->append('Policy')
                ->toString(),
        );
    }

    /**
     * المدير العام بيتجاوز **الصلاحيات** — مش **قواعد السلامة**.
     *
     * لو رجّعنا true دايماً، هيقدر يحذف نفسه أو ينشر سجل ناقص.
     * القدرات المحمية بـ invariants() بتكمّل للـ Policy عادي.
     *
     * الدور بيتسأل عنه على **الفريق المركزي** صراحةً — مش الفريق الحالي.
     * جوه لوحة مؤسسة، `hasRole()` كان هيدوّر على فريق المؤسسة ويرجّع false
     * (docs/24 بند ٢).
     */
    private function registerSuperAdminBypass(): void
    {
        Gate::before(function (Authenticatable $user, string $ability, array $arguments = []): ?bool {
            if (! app(CentralRoles::class)->isSuperAdmin($user)) {
                return null;   // ← null مش false، عشان الـ Policy تكمّل
            }

            if (app(InvariantRegistry::class)->guards($ability, $arguments[0] ?? null)) {
                return null;
            }

            return true;
        });
    }

    /**
     * القدرات اللي مالهاش موديل (الصفحات والودجتس) بتتعرّف كـ Gates
     * من الكونفيج — مش بتتسأل كنص صلاحية في الكود.
     */
    private function registerAbilityGates(): void
    {
        $builder = $this->app->make(PermissionBuilder::class);

        foreach ([...$builder->pageAbilities(), ...$builder->widgetAbilities()] as $ability) {
            Gate::define($ability, static function (Authenticatable $user) use ($ability): Response {
                if (! method_exists($user, 'hasPermissionTo')) {
                    return Response::deny();
                }

                try {
                    $allowed = (bool) $user->hasPermissionTo($ability, config('authorization.guard'));
                } catch (PermissionDoesNotExist) {
                    $allowed = false;
                }

                return $allowed
                    ? Response::allow()
                    : Response::deny(__('authorization.denied.missing_permission', [
                        'permission' => permission_label($ability),
                    ]));
            });
        }
    }
}
