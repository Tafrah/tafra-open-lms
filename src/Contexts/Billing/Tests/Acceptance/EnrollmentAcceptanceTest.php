<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Pages\ListEnrollments;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة Enrollment ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = Enrollment::factory()->create();

    Livewire::test(ListEnrollments::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في Enrollment', function (): void {
    actingAsRole('viewer');

    $this->get(EnrollmentResource::getUrl('index'))->assertOk();
});

it('شاشة Enrollment مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = Enrollment::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => Enrollment::factory()->create());

    Livewire::test(ListEnrollments::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
