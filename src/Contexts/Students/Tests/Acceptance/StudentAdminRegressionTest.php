<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Pages\ListCoupons;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\CreateStudent;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\ListStudents;

/*
| مراجعة اللوحة (نوفمبر ٢٠٢٦) — حاجات كانت بايظة للمدرس.
*/

it('الموبايل بـ +20 أو بأرقام عربية بيتقبل ويتخزّن موحّد', function (string $typed): void {
    actingAsRole('admin');

    Livewire::test(CreateStudent::class)
        ->fillForm(['name' => 'طالب', 'phone' => $typed, 'password' => 'student-pass-1'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Student::query()->where('phone', '01012345678')->exists())->toBeTrue();
})->with(['+20 10 1234 5678', '٠١٠١٢٣٤٥٦٧٨']);

it('رقم موبايل متسجّل قبل كده (حتى بشكل مختلف) مرفوض', function (): void {
    actingAsRole('admin');
    Student::factory()->create(['phone' => '01012345678']);

    Livewire::test(CreateStudent::class)
        ->fillForm(['name' => 'طالب', 'phone' => '+201012345678', 'password' => 'student-pass-1'])
        ->call('create')
        ->assertHasFormErrors(['phone']);
});

it('سحب أكتر من الرصيد بيطلع رسالة مش 500', function (): void {
    actingAsRole('admin');
    $student = Student::factory()->create();

    Livewire::test(ListStudents::class)
        ->loadTable()
        ->callTableAction('wallet', $student, data: ['type' => 'withdraw', 'amount' => 500])
        ->assertNotified(__('billing::billing.errors.insufficient_balance', ['balance' => fc_number(0.0)]));

    expect((float) $student->fresh()->balance)->toBe(0.0);
});

it('المشاهد مايقدرش يوقف كوبون من الجدول', function (): void {
    inTenant();
    $coupon = Coupon::factory()->create(['is_active' => true]);
    actingAsRole('viewer');

    try {
        Livewire::test(ListCoupons::class)->loadTable()
            ->call('updateTableColumnState', 'is_active', (string) $coupon->getKey(), false);
    } catch (Throwable) {
        // مرفوض
    }

    expect($coupon->fresh()->is_active)->toBeTrue();
});
