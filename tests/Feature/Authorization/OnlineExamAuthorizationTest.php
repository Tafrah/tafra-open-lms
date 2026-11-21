<?php

declare(strict_types=1);

use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

dataset('online_exams_OnlineExam_roles', [
    ['super_admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true]],
    ['admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true]],
    ['assistant', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false]],
    ['viewer', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false]],
]);

it('يطبّق مصفوفة صلاحيات OnlineExam', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $record = OnlineExam::factory()->create();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? OnlineExam::class : $record;

        expect($user->can($ability, $argument))->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('online_exams_OnlineExam_roles');

it('سجل من مؤسسة تانية مش موجود بالنسبة للمدير', function (): void {
    $other = Tenant::factory()->create();
    app(TenantContext::class)->set($other->getKey());
    $foreign = OnlineExam::factory()->create();

    $admin = userWithRole('admin');

    expect($admin->can('view', $foreign))->toBeFalse()
        ->and(OnlineExam::query()->whereKey($foreign->getKey())->exists())->toBeFalse();
});
