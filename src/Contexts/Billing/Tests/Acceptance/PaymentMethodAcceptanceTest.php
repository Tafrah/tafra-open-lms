<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Pages\ManagePaymentMethods;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة PaymentMethod ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = PaymentMethod::factory()->create();

    Livewire::test(ManagePaymentMethods::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في PaymentMethod', function (): void {
    actingAsRole('viewer');

    $this->get(PaymentMethodResource::getUrl('index'))->assertOk();
});

it('شاشة PaymentMethod مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = PaymentMethod::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => PaymentMethod::factory()->create());

    Livewire::test(ManagePaymentMethods::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
