<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource\Pages\ListBootcamps;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة Bootcamp ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = Bootcamp::factory()->draft()->create();

    Livewire::test(ListBootcamps::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في Bootcamp', function (): void {
    actingAsRole('viewer');

    $this->get(BootcampResource::getUrl('index'))->assertOk();
});

it('شاشة Bootcamp مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = Bootcamp::factory()->draft()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => Bootcamp::factory()->draft()->create());

    Livewire::test(ListBootcamps::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
