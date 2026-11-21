<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Pages\ListPaymentInvoices;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Pages\ViewPaymentInvoice;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة PaymentInvoice ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = PaymentInvoice::factory()->create();

    Livewire::test(ListPaymentInvoices::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في PaymentInvoice', function (): void {
    actingAsRole('viewer');

    $this->get(PaymentInvoiceResource::getUrl('index'))->assertOk();
});

it('شاشة PaymentInvoice مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = PaymentInvoice::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => PaymentInvoice::factory()->create());

    Livewire::test(ListPaymentInvoices::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('صفحة عرض PaymentInvoice بتفتح', function (): void {
    actingAsRole('admin');
    $record = PaymentInvoice::factory()->create();

    Livewire::test(ViewPaymentInvoice::class, ['record' => $record->getKey()])->assertOk();
});
