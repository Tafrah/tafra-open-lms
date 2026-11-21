<?php

declare(strict_types=1);

use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

dataset('money_transactions_MoneyTransaction_roles', [
    ['super_admin', ['viewAny' => true, 'view' => true]],
    ['admin', ['viewAny' => true, 'view' => true]],
    ['assistant', ['viewAny' => false, 'view' => false]],
    ['viewer', ['viewAny' => true, 'view' => true]],
]);

it('يطبّق مصفوفة صلاحيات MoneyTransaction', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $record = MoneyTransaction::factory()->create();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? MoneyTransaction::class : $record;

        expect($user->can($ability, $argument))->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('money_transactions_MoneyTransaction_roles');

it('سجل من مؤسسة تانية مش موجود بالنسبة للمدير', function (): void {
    $other = Tenant::factory()->create();
    app(TenantContext::class)->set($other->getKey());
    $foreign = MoneyTransaction::factory()->create();

    $admin = userWithRole('admin');

    expect($admin->can('view', $foreign))->toBeFalse()
        ->and(MoneyTransaction::query()->whereKey($foreign->getKey())->exists())->toBeFalse();
});
