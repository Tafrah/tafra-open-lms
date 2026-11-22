<?php

declare(strict_types=1);

use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Domain\Models\StudentDevice;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
| صلاحية `manage_devices.students`: المدير والمساعد — مش المحرر ولا المشاهد.
*/

dataset('student_device_roles', [
    ['super_admin', true],
    ['admin', true],
    ['assistant', true],
    ['editor', false],
    ['viewer', false],
]);

it('مصفوفة صلاحيات أجهزة الطالب', function (string $role, bool $allowed): void {
    $user = userWithRole($role);
    $student = Student::factory()->create();
    $device = StudentDevice::query()->create(['student_id' => $student->getKey(), 'token_hash' => StudentDevice::hashToken('x')]);

    expect($user->can('manageDevices', $student))->toBe($allowed, "{$role} manageDevices")
        ->and($user->can('viewAny', StudentDevice::class))->toBe($allowed, "{$role} viewAny")
        ->and($user->can('view', $device))->toBe($allowed, "{$role} view")
        ->and($user->can('delete', $device))->toBe($allowed, "{$role} delete");

    // مفيش إضافة ولا تعديل من اللوحة — الأجهزة بتتسجّل من الموقع بس
    // (المدير العام بيعدّي الصلاحيات كلها بـ Gate::before، فمش بيتسأل هنا)
    if ($role !== 'super_admin') {
        expect($user->can('create', StudentDevice::class))->toBeFalse()
            ->and($user->can('update', $device))->toBeFalse();
    }
})->with('student_device_roles');

it('طالب محذوف مايتفصلش أجهزته (استرجعه الأول)', function (): void {
    $admin = userWithRole('admin');
    $student = Student::factory()->create();
    $student->delete();

    expect($admin->can('manageDevices', $student))->toBeFalse();
});

it('أجهزة طالب في مؤسسة تانية مش موجودة بالنسبة للمدير', function (): void {
    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), function (): array {
        $student = Student::factory()->create();

        return [$student, StudentDevice::query()->create(['student_id' => $student->getKey(), 'token_hash' => StudentDevice::hashToken('y')])];
    });

    $admin = userWithRole('admin');

    expect($admin->can('manageDevices', $foreign[0]))->toBeFalse()
        ->and($admin->can('view', $foreign[1]))->toBeFalse()
        ->and(StudentDevice::query()->whereKey($foreign[1]->getKey())->exists())->toBeFalse();
});
