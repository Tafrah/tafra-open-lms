<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource\Pages\ManageEducationalLevels;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة EducationalLevel ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = EducationalLevel::factory()->create();

    Livewire::test(ManageEducationalLevels::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في EducationalLevel', function (): void {
    actingAsRole('viewer');

    $this->get(EducationalLevelResource::getUrl('index'))->assertOk();
});

it('شاشة EducationalLevel مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = EducationalLevel::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => EducationalLevel::factory()->create());

    Livewire::test(ManageEducationalLevels::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
