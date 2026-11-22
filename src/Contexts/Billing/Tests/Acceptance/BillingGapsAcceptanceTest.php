<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\GenerateRechargeCardsAction;
use Src\Contexts\Billing\Application\DataObjects\RechargeCardBatchData;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentMethodType;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\CouponRedemption;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Contexts\Billing\Presentation\Filament\RelationManagers\StudentInvoicesRelationManager;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Pages\EditCoupon;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\Pages\ListCoupons;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource\RelationManagers\RedemptionsRelationManager;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource\Pages\ListEnrollments;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource\Pages\ManagePaymentMethods;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\EditStudent;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

/*
| فجوات الفلوس من docs/product/plan.html: التفعيل بالجملة، الكوبونات، طرق الدفع،
| طباعة الكروت، وفواتير الطالب.
*/

it('المساعد بيفعّل كورس بأرقام الموبايل من شاشة الاشتراكات، والنتيجة في إشعار', function (): void {
    actingAsRole('assistant');
    $course = Bootcamp::factory()->create();
    $student = Student::factory()->create(['phone' => '01031313131']);

    Livewire::test(ListEnrollments::class)
        ->assertActionVisible('activateByPhones')
        ->callAction('activateByPhones', data: [
            'bootcamp_id' => $course->getKey(),
            'phones' => "+20 103 131 3131\n01000000404",
            'charge_wallet' => false,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified(__('billing::billing.enrollment.bulk.activate_done', ['count' => '1']));

    expect(app(BootcampAccess::class)->has($student, $course))->toBeTrue();

    Livewire::test(ListEnrollments::class)
        ->callAction('cancelByPhones', data: ['bootcamp_id' => $course->getKey(), 'phones' => '01031313131'])
        ->assertHasNoActionErrors();

    app(BootcampAccess::class)->forget();
    expect(app(BootcampAccess::class)->has($student, $course))->toBeFalse();
});

it('اشتراك طالب واحد من نفس الشاشة', function (): void {
    actingAsRole('admin');
    $course = Bootcamp::factory()->create();
    $student = Student::factory()->create();

    Livewire::test(ListEnrollments::class)
        ->callAction('enrollStudent', data: ['student_id' => $student->getKey(), 'bootcamp_id' => $course->getKey(), 'charge_wallet' => false])
        ->assertHasNoActionErrors();

    expect(app(BootcampAccess::class)->has($student, $course))->toBeTrue();
});

it('المشاهد مايشوفش زراير التفعيل', function (): void {
    actingAsRole('viewer');

    Livewire::test(ListEnrollments::class)
        ->assertActionHidden('activateByPhones')
        ->assertActionHidden('cancelByPhones')
        ->assertActionHidden('enrollStudent');
});

it('جدول الكوبونات بيعرض إجمالي الخصم، وصفحة الكوبون فيها الاستخدامات', function (): void {
    actingAsRole('admin');
    $coupon = Coupon::factory()->create();
    $invoice = PaymentInvoice::factory()->create(['coupon_id' => $coupon->getKey(), 'status' => InvoiceStatus::Paid]);
    foreach ([30, 20] as $discount) {
        CouponRedemption::query()->create([
            'coupon_id' => $coupon->getKey(),
            'student_id' => $invoice->student_id,
            'payment_invoice_id' => $invoice->getKey(),
            'discount_amount' => $discount,
            'original_amount' => 150,
            'final_amount' => 150 - $discount,
            'redeemed_at' => now(),
        ]);
    }

    Livewire::test(ListCoupons::class)
        ->loadTable()
        ->assertTableColumnStateSet('redemptions_sum_discount_amount', 50.0, $coupon);

    Livewire::test(RedemptionsRelationManager::class, ['ownerRecord' => $coupon, 'pageClass' => EditCoupon::class])
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords(CouponRedemption::query()->get())
        ->assertSee($invoice->number)
        ->assertSee($invoice->student->name);
});

it('طرق الدفع: اتصالات كاش وأورانج كاش، والجدول بيعرض المستخدم من الحد', function (): void {
    actingAsRole('admin');
    $method = PaymentMethod::factory()->create(['type' => PaymentMethodType::EtisalatCash, 'monthly_limit' => 1000]);
    PaymentMethod::factory()->create(['type' => PaymentMethodType::OrangeCash]);
    $invoice = PaymentInvoice::factory()->create(['amount' => 250, 'status' => InvoiceStatus::Paid, 'paid_at' => now()]);
    PaymentReceipt::factory()->create([
        'payment_invoice_id' => $invoice->getKey(), 'student_id' => $invoice->student_id,
        'payment_method_id' => $method->getKey(), 'amount' => 250, 'status' => ReceiptStatus::Approved,
    ]);

    expect(PaymentMethodType::EtisalatCash->getLabel())->not->toContain('billing::')
        ->and(PaymentMethodType::OrangeCash->getLabel())->not->toContain('billing::')
        ->and(PaymentMethodType::OrangeCash->isMobileWallet())->toBeTrue();

    Livewire::test(ManagePaymentMethods::class)
        ->loadTable()
        ->assertTableColumnStateSet('monthly_limit', __('billing::billing.method.usage_of', ['used' => '250', 'limit' => '1,000']), $method)
        ->assertSee(PaymentMethodType::OrangeCash->getLabel());
});

it('صفحة طباعة الكروت فيها اسم المنصة (من غير لوجو) ومسلسل لكل كارت', function (): void {
    $admin = actingAsRole('admin');
    $group = app(GenerateRechargeCardsAction::class)->handle(new RechargeCardBatchData('Center A', RechargeCardType::Wallet, 3, 50.0), $admin);

    $response = $this->get(route('billing.recharge-cards.print', $group->getKey()))->assertOk();

    $response->assertSee($group->getKey().'-0001')
        ->assertSee($group->getKey().'-0003')
        ->assertSee(Tenant::query()->find(currentTenant()->getKey())->getTranslation('name', app()->getLocale(), true));
});

it('صفحة الطباعة بتعرض لوجو الأكاديمية لو موجود', function (): void {
    $tenant = currentTenant();
    $tenant->forceFill(['logo_path' => 'branding/logo.png'])->save();
    $admin = actingAsRole('admin');
    $group = app(GenerateRechargeCardsAction::class)->handle(new RechargeCardBatchData('Center B', RechargeCardType::Wallet, 1, 20.0), $admin);

    $this->get(route('billing.recharge-cards.print', $group->getKey()))
        ->assertOk()
        ->assertSee('branding/logo.png')
        ->assertSee('class="logo"', false);
});

it('صفحة الطالب فيها تبويب فواتيره بس', function (): void {
    actingAsRole('assistant');
    $student = Student::factory()->create();
    $mine = PaymentInvoice::factory()->create(['student_id' => $student->getKey()]);
    $other = PaymentInvoice::factory()->create();

    Livewire::test(StudentInvoicesRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other])
        ->assertTableActionVisible('open', $mine);
});
