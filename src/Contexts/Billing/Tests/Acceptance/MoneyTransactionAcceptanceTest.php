<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource\Pages\ListMoneyTransactions;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة MoneyTransaction ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = MoneyTransaction::factory()->create();

    Livewire::test(ListMoneyTransactions::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في MoneyTransaction', function (): void {
    actingAsRole('viewer');

    $this->get(MoneyTransactionResource::getUrl('index'))->assertOk();
});

it('شاشة MoneyTransaction مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = MoneyTransaction::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => MoneyTransaction::factory()->create());

    Livewire::test(ListMoneyTransactions::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
