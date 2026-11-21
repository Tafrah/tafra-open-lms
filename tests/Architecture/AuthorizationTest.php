<?php

declare(strict_types=1);

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Src\Support\Domain\Authorization\Policy;

/*
|--------------------------------------------------------------------------
| الاختبارات اللي بتفرض قاعدة التفويض (docs/19 بند ٩)
|--------------------------------------------------------------------------
*/

it('لا يُستخدم فحص صلاحية مباشر إلا في طبقة التفويض', function (): void {
    // طبقة التفويض نفسها — المكان الوحيد المسموح فيه
    $allowedClasses = [
        'Src\Support\Domain\Authorization\Decision',
        'Src\Support\Infrastructure\Authorization\InvariantRegistry',
        'App\Providers\AuthorizationServiceProvider',
    ];

    // الـ Policies مسموح لها — هي طبقة التفويض.
    // والميدلوير اللي بيفرض 2FA بيسأل عن الدور كشرط دخول للوحة كلها.
    $allowedPatterns = [
        '#/Infrastructure/Policies/#',
        '#/Middleware/EnsureTwoFactorIsConfigured\.php$#',
    ];

    $violations = [];

    foreach (allPhpFiles([src_path(), app_path()]) as $file) {
        if (in_array(classFromPath($file->getPathname()), $allowedClasses, true)) {
            continue;
        }

        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, $file->getPathname())) {
                continue 2;
            }
        }

        if (preg_match('/->(hasPermissionTo|hasRole|hasAnyRole|hasAllRoles)\(/', phpCodeWithoutComments($file->getContents()), $found)) {
            $violations[] = "{$file->getRelativePathname()}: {$found[0]}";
        }
    }

    expect($violations)->toBeEmpty(
        "فحص صلاحية مباشر خارج طبقة التفويض:\n".implode("\n", $violations),
    );
});

it('لا تُستخدم أسماء الصلاحيات كنصوص في can()', function (): void {
    $separator = preg_quote((string) config('authorization.separator'), '/');
    $violations = [];

    foreach (allPhpFiles([src_path(), app_path()]) as $file) {
        if (! preg_match_all("/->can\(\s*'([a-z_]+{$separator}[a-z_.]+)'\s*[,)]/", phpCodeWithoutComments($file->getContents()), $found)) {
            continue;
        }

        foreach ($found[1] as $permission) {
            // استثناء: قدرات الصفحات والودجتس ليها Gates معرّفة
            if (str_starts_with($permission, 'access.') || str_starts_with($permission, 'widget.')) {
                continue;
            }

            $violations[] = "{$file->getRelativePathname()}: can('{$permission}')";
        }
    }

    expect($violations)->toBeEmpty(
        "اسم صلاحية بدل اسم قدرة — استخدم can('ability', \$model):\n".implode("\n", $violations),
    );
});

it('لا يُستخدم skipAuthorization', function (): void {
    expect(grepAll('/->skipAuthorization\(/', [src_path(), app_path()]))->toBeEmpty();
});

it('كل موديل دومين له Policy', function (): void {
    $violations = [];

    foreach (allDomainModels() as $model) {
        if (Gate::getPolicyFor($model) === null) {
            $violations[] = $model;
        }
    }

    expect($violations)->toBeEmpty('موديلات بدون Policy: '.implode(', ', $violations));
});

it('كل Policy ترجّع Response وليس bool', function (): void {
    $violations = [];
    $ignored = ['invariants', 'isInvariant', 'permissionFor', 'resourceName', 'area'];

    foreach (allPolicies() as $policy) {
        foreach ((new ReflectionClass($policy))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (in_array($method->name, $ignored, true) || $method->isConstructor()) {
                continue;
            }

            if ((string) $method->getReturnType() !== Response::class) {
                $violations[] = "{$policy}::{$method->name}()";
            }
        }
    }

    expect($violations)->toBeEmpty("دوال Policy مش بترجّع Response:\n".implode("\n", $violations));
});

it('كل Policy ترث الكلاس الأساسي', function (): void {
    $violations = [];

    foreach (allPolicies() as $policy) {
        if (! is_subclass_of($policy, Policy::class)) {
            $violations[] = $policy;
        }
    }

    expect($violations)->toBeEmpty('سياسات مش وارثة الأساس: '.implode(', ', $violations));
});

it('لا يوجد Gate::policy يدوي — الاكتشاف بالاصطلاح', function (): void {
    expect(grepAll('/Gate::policy\(/', [src_path(), app_path()]))->toBeEmpty();
});
