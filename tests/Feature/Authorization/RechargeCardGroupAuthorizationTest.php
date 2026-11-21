<?php

declare(strict_types=1);

use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

dataset('recharge_cards_RechargeCardGroup_roles', [
    ['super_admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true, 'print' => true]],
    ['admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true, 'print' => true]],
    ['assistant', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false, 'print' => false]],
    ['viewer', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false, 'print' => false]],
]);

it('يطبّق مصفوفة صلاحيات RechargeCardGroup', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $record = RechargeCardGroup::factory()->create();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? RechargeCardGroup::class : $record;

        expect($user->can($ability, $argument))->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('recharge_cards_RechargeCardGroup_roles');

it('سجل من مؤسسة تانية مش موجود بالنسبة للمدير', function (): void {
    $other = Tenant::factory()->create();
    app(TenantContext::class)->set($other->getKey());
    $foreign = RechargeCardGroup::factory()->create();

    $admin = userWithRole('admin');

    expect($admin->can('view', $foreign))->toBeFalse()
        ->and(RechargeCardGroup::query()->whereKey($foreign->getKey())->exists())->toBeFalse();
});
