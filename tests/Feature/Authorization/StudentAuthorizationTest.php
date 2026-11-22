<?php

declare(strict_types=1);

use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

dataset('students_Student_roles', [
    ['super_admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true, 'enroll' => true, 'adjustWallet' => true, 'resetPassword' => true, 'block' => true, 'manageDevices' => true]],
    ['admin', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => true, 'enroll' => true, 'adjustWallet' => true, 'resetPassword' => true, 'block' => true, 'manageDevices' => true]],
    ['assistant', ['viewAny' => true, 'view' => true, 'create' => true, 'update' => true, 'delete' => false, 'enroll' => true, 'adjustWallet' => false, 'resetPassword' => true, 'block' => true, 'manageDevices' => true]],
    ['viewer', ['viewAny' => true, 'view' => true, 'create' => false, 'update' => false, 'delete' => false, 'enroll' => false, 'adjustWallet' => false, 'resetPassword' => false, 'block' => false, 'manageDevices' => false]],
]);

it('يطبّق مصفوفة صلاحيات Student', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $record = Student::factory()->create();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? Student::class : $record;

        expect($user->can($ability, $argument))->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('students_Student_roles');

it('سجل من مؤسسة تانية مش موجود بالنسبة للمدير', function (): void {
    $other = Tenant::factory()->create();
    app(TenantContext::class)->set($other->getKey());
    $foreign = Student::factory()->create();

    $admin = userWithRole('admin');

    expect($admin->can('view', $foreign))->toBeFalse()
        ->and(Student::query()->whereKey($foreign->getKey())->exists())->toBeFalse();
});
