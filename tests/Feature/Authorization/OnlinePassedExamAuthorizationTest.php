<?php

declare(strict_types=1);

use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

dataset('exam_attempts_OnlinePassedExam_roles', [
    ['super_admin', ['viewAny' => true, 'view' => true, 'delete' => true, 'grade' => true]],
    ['admin', ['viewAny' => true, 'view' => true, 'delete' => true, 'grade' => true]],
    ['assistant', ['viewAny' => true, 'view' => true, 'delete' => false, 'grade' => true]],
    ['viewer', ['viewAny' => true, 'view' => true, 'delete' => false, 'grade' => false]],
]);

it('يطبّق مصفوفة صلاحيات OnlinePassedExam', function (string $role, array $expected): void {
    $user = userWithRole($role);
    $record = OnlinePassedExam::factory()->create(['finished' => true]);

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? OnlinePassedExam::class : $record;

        expect($user->can($ability, $argument))->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('exam_attempts_OnlinePassedExam_roles');

it('سجل من مؤسسة تانية مش موجود بالنسبة للمدير', function (): void {
    $other = Tenant::factory()->create();
    app(TenantContext::class)->set($other->getKey());
    $foreign = OnlinePassedExam::factory()->create(['finished' => true]);

    $admin = userWithRole('admin');

    expect($admin->can('view', $foreign))->toBeFalse()
        ->and(OnlinePassedExam::query()->whereKey($foreign->getKey())->exists())->toBeFalse();
});
