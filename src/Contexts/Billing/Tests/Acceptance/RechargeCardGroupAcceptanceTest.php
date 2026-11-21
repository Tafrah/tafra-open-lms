<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Pages\ListRechargeCardGroups;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource\Pages\ViewRechargeCardGroup;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة RechargeCardGroup ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = RechargeCardGroup::factory()->create();

    Livewire::test(ListRechargeCardGroups::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في RechargeCardGroup', function (): void {
    actingAsRole('viewer');

    $this->get(RechargeCardGroupResource::getUrl('index'))->assertOk();
});

it('شاشة RechargeCardGroup مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = RechargeCardGroup::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => RechargeCardGroup::factory()->create());

    Livewire::test(ListRechargeCardGroups::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('صفحة عرض RechargeCardGroup بتفتح', function (): void {
    actingAsRole('admin');
    $record = RechargeCardGroup::factory()->create();

    Livewire::test(ViewRechargeCardGroup::class, ['record' => $record->getKey()])->assertOk();
});
