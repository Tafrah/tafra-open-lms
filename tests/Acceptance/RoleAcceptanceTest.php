<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Identity\Domain\Models\Role;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Pages\CreateRole;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource\Pages\ListRoles;
use Src\Support\Infrastructure\Authorization\CentralRoles;

/*
|--------------------------------------------------------------------------
| معايير قبول شاشة الأدوار (docs/19 · docs/24 بند ٢)
|--------------------------------------------------------------------------
*/

it('المدير بيشوف أدوار المؤسسة من غير أدوار فريق التشغيل', function (): void {
    actingAsRole('admin');

    Livewire::test(ListRoles::class)
        ->loadTable()
        ->assertCanSeeTableRecords(Role::query()->whereIn('name', ['admin', 'editor', 'viewer'])->get())
        ->assertCanNotSeeTableRecords(Role::query()->whereIn('name', CentralRoles::catalog())->get());
});

it('إنشاء دور بالصلاحيات المختارة من الشاشة', function (): void {
    actingAsRole('admin');

    Livewire::test(CreateRole::class)
        ->fillForm([
            'name' => 'reviewer',
            'permission_groups' => ['content' => ['view_any.announcements', 'view.announcements']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::query()->where('name', 'reviewer')->firstOrFail();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['view.announcements', 'view_any.announcements']);
});

it('اسم الدور إلزامي', function (): void {
    actingAsRole('admin');

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('المحرر مايقدرش يفتح شاشة الأدوار بالرابط المباشر', function (): void {
    actingAsRole('editor');

    $this->get(RoleResource::getUrl('index'))->assertForbidden();
});
