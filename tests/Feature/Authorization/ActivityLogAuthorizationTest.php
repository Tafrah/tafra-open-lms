<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Audit\Domain\Models\ActivityLog;

/*
|--------------------------------------------------------------------------
| مصفوفة الأدوار × القدرات — سجل النشاط
|--------------------------------------------------------------------------
| `ActivityLogPolicy::area()` = `access.system` → `admin` و`super_admin` بس.
| والسجل **مالوش تعديل ولا حذف** لأي دور — سجل التدقيق مايتغيّرش،
| والتنظيف بيتم بأمر مجدول مش من الشاشة.
*/

dataset('activity_log_roles_and_abilities', [
    ['super_admin', ['viewAny' => true, 'create' => false, 'update' => false, 'delete' => false]],
    ['admin', ['viewAny' => true, 'create' => false, 'update' => false, 'delete' => false]],
    ['editor', ['viewAny' => false, 'create' => false, 'update' => false, 'delete' => false]],
    ['viewer', ['viewAny' => false, 'create' => false, 'update' => false, 'delete' => false]],
]);

it('يطبّق الصلاحيات الصحيحة لكل دور', function (string $role, array $expected): void {
    $user = userWithRole($role);

    activity('identity')->causedBy($user)->log('created');
    $log = ActivityLog::query()->latest('id')->firstOrFail();

    foreach ($expected as $ability => $allowed) {
        $argument = in_array($ability, ['viewAny', 'create'], true) ? ActivityLog::class : $log;

        expect($user->can($ability, $argument))
            ->toBe($allowed, "الدور {$role} — القدرة {$ability}");
    }
})->with('activity_log_roles_and_abilities');

it('لا يستطيع المدير العام تعديل أو حذف سجل تدقيق', function (): void {
    $super = userWithRole('super_admin');

    activity('security')->causedBy($super)->log('force_logout');
    $log = ActivityLog::query()->latest('id')->firstOrFail();

    // معاه كل الصلاحيات...
    expect(Gate::forUser($super)->allows('viewAny', ActivityLog::class))->toBeTrue();

    // ...ومع ذلك السجل غير قابل للتعديل أو الحذف
    expect(Gate::forUser($super)->allows('update', $log))->toBeFalse()
        ->and(Gate::forUser($super)->allows('delete', $log))->toBeFalse();
});
