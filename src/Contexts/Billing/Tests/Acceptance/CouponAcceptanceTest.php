<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Pages\ListCoupons;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

it('المدير بيفتح شاشة Coupon ويشوف السجلات', function (): void {
    actingAsRole('admin');
    $record = Coupon::factory()->create();

    Livewire::test(ListCoupons::class)
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$record]);
});

it('المشاهد مايشوفش زراير التعديل في Coupon', function (): void {
    actingAsRole('viewer');

    $this->get(CouponResource::getUrl('index'))->assertOk();
});

it('شاشة Coupon مابتعرضش سجلات مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $mine = Coupon::factory()->create();

    $other = Tenant::factory()->create();
    $foreign = app(TenantContext::class)->forTenant($other->getKey(), fn () => Coupon::factory()->create());

    Livewire::test(ListCoupons::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$foreign]);
});
