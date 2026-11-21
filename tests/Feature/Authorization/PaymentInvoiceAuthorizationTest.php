<?php

declare(strict_types=1);

use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

dataset('payment_invoices_PaymentInvoice_roles', [
    ['super_admin', ['viewAny' => true, 'view' => true, 'approve' => true, 'reject' => true]],
    ['admin', ['viewAny' => true, 'view' => true, 'approve' => true, 'reject' => true]],
    ['assistant', ['viewAny' => true, 'view' => true, 'approve' => true, 'reject' => true]],
    ['viewer', ['viewAny' => true, 'view' => true, 'approve' => false, 'reject' => false]],
]);

it('يطبّق مصفوفة صلاحيات PaymentInvoice', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $record = PaymentInvoice::factory()->create();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? PaymentInvoice::class : $record;

        expect($user->can($ability, $argument))->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('payment_invoices_PaymentInvoice_roles');

it('سجل من مؤسسة تانية مش موجود بالنسبة للمدير', function (): void {
    $other = Tenant::factory()->create();
    app(TenantContext::class)->set($other->getKey());
    $foreign = PaymentInvoice::factory()->create();

    $admin = userWithRole('admin');

    expect($admin->can('view', $foreign))->toBeFalse()
        ->and(PaymentInvoice::query()->whereKey($foreign->getKey())->exists())->toBeFalse();
});
