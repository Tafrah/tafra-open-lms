<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| مصفوفة الأدوار × القدرات
|--------------------------------------------------------------------------
*/

dataset('user_roles_and_abilities', [
    ['super_admin', ['viewAny' => true, 'create' => true, 'update' => true, 'delete' => true, 'impersonate' => true]],
    ['admin', ['viewAny' => true, 'create' => true, 'update' => true, 'delete' => true, 'impersonate' => true]],
    ['editor', ['viewAny' => true, 'create' => false, 'update' => false, 'delete' => false, 'impersonate' => false]],
    ['viewer', ['viewAny' => true, 'create' => false, 'update' => false, 'delete' => false, 'impersonate' => false]],
]);

it('يطبّق الصلاحيات الصحيحة لكل دور', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $target = userWithRole('viewer');

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? User::class : $target;

        expect($user->can($ability, $argument))
            ->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('user_roles_and_abilities');

/*
|--------------------------------------------------------------------------
| قواعد السلامة — كل واحدة برسالتها
|--------------------------------------------------------------------------
*/

it('لا يستطيع أي مستخدم حذف نفسه', function (string $role): void {
    $user = userWithRole($role);

    $response = Gate::forUser($user)->inspect('delete', $user);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.self_target'));
})->with(['super_admin', 'admin']);

it('لا يتجاوز المدير العام قواعد السلامة', function (): void {
    $super = userWithRole('super_admin');

    // معاه الصلاحية...
    expect(Gate::forUser($super)->allows('viewAny', User::class))->toBeTrue();

    // ...ومع ذلك القاعدة بتمنعه من حذف نفسه
    expect(Gate::forUser($super)->allows('delete', $super))->toBeFalse();
});

it('يمنع انتحال شخصية المدير العام', function (): void {
    $super = userWithRole('super_admin');
    $anotherSuper = userWithRole('super_admin');

    $response = Gate::forUser($super)->inspect('impersonate', $anotherSuper);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe(__('authorization.denied.user.cannot_impersonate_super_admin'));
});

it('يمنع حذف آخر مدير في المؤسسة', function (): void {
    $admin = userWithRole('admin');
    $super = userWithRole('super_admin');

    // الأدمن الوحيد غير المدير العام — نحذف المدير العام الأول
    $super->tenants()->detach(currentTenant()->getKey());

    $response = Gate::forUser($admin)->inspect('delete', $admin);

    expect($response->denied())->toBeTrue();
});

it('يخفي سجل مستخدم من مؤسسة أخرى بـ 404 لا 403', function (): void {
    $a = currentTenant();
    $b = Tenant::factory()->create();

    $user = userWithRole('admin', $a);
    $foreign = userWithRole('admin', $b);

    app(TenantContext::class)->set($a->getKey());

    $response = Gate::forUser($user)->inspect('view', $foreign);

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});

/*
|--------------------------------------------------------------------------
| الواجهة — الإخفاء والمنع
|--------------------------------------------------------------------------
*/

it('يمنع الوصول المباشر بالـ URL لمن لا يملك الصلاحية', function (): void {
    actingAsRole('viewer');

    $this->get(UserResource::getUrl('create'))->assertForbidden();
});

it('يسمح للمدير بصفحة الإنشاء', function (): void {
    actingAsRole('admin');

    $this->get(UserResource::getUrl('create'))->assertOk();
});
