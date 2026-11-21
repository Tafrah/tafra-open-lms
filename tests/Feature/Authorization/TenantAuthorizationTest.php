<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| مصفوفة الأدوار × القدرات — المؤسسات
|--------------------------------------------------------------------------
| المؤسسات بتتدار من `/central` (docs/24 بند ٣). الصلاحيات على الفريق
| المركزي: أدوار المؤسسة (حتى `admin`) مالهاش أي منها.
*/

dataset('tenant_roles_and_abilities', [
    ['admin', ['viewAny' => false, 'create' => false, 'update' => false, 'suspend' => false]],
    ['editor', ['viewAny' => false, 'create' => false, 'update' => false, 'suspend' => false]],
    ['viewer', ['viewAny' => false, 'create' => false, 'update' => false, 'suspend' => false]],
]);

it('أدوار المؤسسة مالهاش أي قدرة على المؤسسات', function (string $role, array $expected): void {
    $tenant = currentTenant();
    $user = userWithRole($role, $tenant);

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? Tenant::class : $tenant;

        expect($user->can($ability, $argument))
            ->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('tenant_roles_and_abilities');

it('يطبّق صلاحيات فريق التشغيل', function (string $role, array $expected): void {
    $tenant = currentTenant();
    Tenant::factory()->create();   // مؤسسة نشطة تانية — عشان قاعدة «آخر مؤسسة» ماتتدخّلش

    $user = centralUser($role);

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? Tenant::class : $tenant;

        expect(Gate::forUser($user)->allows($ability, $argument))
            ->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with([
    ['super_admin', ['viewAny' => true, 'create' => true, 'update' => true, 'suspend' => true, 'impersonate' => true]],
    ['support', ['viewAny' => true, 'create' => false, 'update' => false, 'suspend' => false, 'impersonate' => true]],
]);

it('يمنع حذف أو إيقاف آخر مؤسسة نشطة حتى من المدير العام', function (string $ability): void {
    $tenant = currentTenant();
    $super = centralUser('super_admin');

    Tenant::query()->whereKeyNot($tenant->getKey())->update(['is_active' => false]);

    $response = Gate::forUser($super)->inspect($ability, $tenant);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.tenancy.last_active_tenant'));
})->with(['delete', 'suspend']);

it('فريق التشغيل بيشوف كل المؤسسات من غير عضوية', function (): void {
    $support = centralUser('support');
    $foreign = Tenant::factory()->create();

    app(TenantContext::class)->set(null);

    expect($support->tenants()->count())->toBe(0)
        ->and(Gate::forUser($support)->allows('view', $foreign))->toBeTrue();
});

it('مايدخلش مؤسسة لسه بتتجهّز حتى المدير العام', function (): void {
    $super = centralUser('super_admin');
    $pending = Tenant::factory()->create();
    $pending->forceFill(['provisioning_status' => Tenant::PROVISIONING_PENDING])->save();

    $response = Gate::forUser($super)->inspect('impersonate', $pending);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.tenancy.not_provisioned'));
});
