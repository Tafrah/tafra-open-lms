<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\Role;

/*
|--------------------------------------------------------------------------
| مصفوفة الأدوار × القدرات — الأدوار
|--------------------------------------------------------------------------
| ⚠️ `RolePolicy::area()` = `access.system`. يعني `viewer` و`editor`
| **مايشوفوش الشاشة أصلاً** حتى لو ماسكين `view_any.*` من النمط —
| وده بالظبط الثغرة اللي اتكشفت في المراجعة الأولى (docs/21 بند ٣).
| المصفوفة دي هي اللي بتقفلها.
*/

dataset('role_roles_and_abilities', [
    ['super_admin', ['viewAny' => true, 'create' => true, 'update' => true, 'delete' => true]],
    ['admin', ['viewAny' => true, 'create' => true, 'update' => true, 'delete' => true]],
    ['editor', ['viewAny' => false, 'create' => false, 'update' => false, 'delete' => false]],
    ['viewer', ['viewAny' => false, 'create' => false, 'update' => false, 'delete' => false]],
]);

it('يطبّق الصلاحيات الصحيحة لكل دور', function (string $role, array $expected): void {
    $user = userWithRole($role);

    // دور مخصص للمؤسسة الحالية — الأدوار الافتراضية بقت محمية (مشتركة بين كل المؤسسات)
    $target = Role::create(['name' => 'custom_reviewer', 'guard_name' => config('authorization.guard')]);

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? Role::class : $target;

        expect($user->can($ability, $argument))
            ->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('role_roles_and_abilities');

it('الأدوار الافتراضية من الكونفيج محمية من التعديل والحذف حتى من المدير العام', function (string $name): void {
    $super = userWithRole('super_admin');
    $role = Role::query()->where('name', $name)->firstOrFail();

    expect(Gate::forUser($super)->inspect('update', $role)->message())->toBe(__('authorization.denied.role.protected'))
        ->and(Gate::forUser($super)->allows('delete', $role))->toBeFalse();
})->with(['admin', 'editor', 'viewer']);

it('يمنع حذف الأدوار المحمية حتى من المدير العام', function (): void {
    $super = userWithRole('super_admin');
    $protected = Role::query()->where('name', config('authorization.super_admin_role'))->firstOrFail();

    $response = Gate::forUser($super)->inspect('delete', $protected);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.role.protected'));
});

it('لا يتجاوز المدير العام قاعدة الأدوار المحمية', function (): void {
    $super = userWithRole('super_admin');

    expect(Gate::forUser($super)->allows('viewAny', Role::class))->toBeTrue()
        ->and(Gate::forUser($super)->allows('create', Role::class))->toBeTrue();
});
