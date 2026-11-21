<?php

declare(strict_types=1);

use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

dataset('bootcamps_Bootcamp_roles', [
    ['super_admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true, 'publish' => true]],
    ['admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true, 'publish' => true]],
    ['assistant', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false, 'publish' => false]],
    ['viewer', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false, 'publish' => false]],
]);

it('يطبّق مصفوفة صلاحيات Bootcamp', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $record = Bootcamp::factory()->draft()->create();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? Bootcamp::class : $record;

        expect($user->can($ability, $argument))->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('bootcamps_Bootcamp_roles');

it('سجل من مؤسسة تانية مش موجود بالنسبة للمدير', function (): void {
    $other = Tenant::factory()->create();
    app(TenantContext::class)->set($other->getKey());
    $foreign = Bootcamp::factory()->draft()->create();

    $admin = userWithRole('admin');

    expect($admin->can('view', $foreign))->toBeFalse()
        ->and(Bootcamp::query()->whereKey($foreign->getKey())->exists())->toBeFalse();
});
