<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\ListStudents;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة Student ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = Student::factory()->create();

    Livewire::test(ListStudents::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في Student', function (): void {
    actingAsRole('viewer');

    $this->get(StudentResource::getUrl('index'))->assertOk();
});

it('شاشة Student مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = Student::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => Student::factory()->create());

    Livewire::test(ListStudents::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
