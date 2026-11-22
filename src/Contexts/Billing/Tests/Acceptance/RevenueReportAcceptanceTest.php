<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\WalletAction;
use Src\Contexts\Billing\Application\Queries\RevenueReport as RevenueReportQuery;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Contexts\Billing\Presentation\Filament\Pages\RevenueReport;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/** فاتورة مدفوعة (ولو تحويل: إيصال مقبول على الرقم) */
function paidInvoice(Bootcamp $course, float $amount, PaymentGateway $gateway, ?PaymentMethod $method = null, ?CarbonInterface $at = null): PaymentInvoice
{
    $invoice = PaymentInvoice::factory()->create([
        'bootcamp_id' => $course->getKey(),
        'amount' => $amount,
        'payment_gateway' => $gateway,
        'status' => InvoiceStatus::Paid,
        'paid_at' => $at ?? now(),
    ]);

    if ($method !== null) {
        PaymentReceipt::factory()->create([
            'payment_invoice_id' => $invoice->getKey(),
            'student_id' => $invoice->student_id,
            'payment_method_id' => $method->getKey(),
            'amount' => $amount,
            'status' => ReceiptStatus::Approved,
        ]);
    }

    return $invoice;
}

it('المدير بيفتح تقرير الدخل والمساعد والمشاهد لأ', function (): void {
    actingAsRole('admin');
    $this->get(RevenueReport::getUrl())->assertOk();

    actingAsRole('assistant');
    $this->get(RevenueReport::getUrl())->assertForbidden();

    actingAsRole('viewer');
    $this->get(RevenueReport::getUrl())->assertForbidden();
});

it('التحويلات وكروت الشحن والشراء بالمحفظة سطور منفصلة، والمحفظة مش داخلة في اللي اتحصّل', function (): void {
    inTenant();
    $math = Bootcamp::factory()->create(['name' => 'Math']);
    $physics = Bootcamp::factory()->create(['name' => 'Physics']);
    $vodafone = PaymentMethod::factory()->create(['name' => 'My Vodafone', 'account_number' => '01012345678']);

    paidInvoice($math, 200, PaymentGateway::ManualTransfer, $vodafone);
    paidInvoice($physics, 100, PaymentGateway::ManualTransfer, $vodafone);
    paidInvoice($math, 150, PaymentGateway::Wallet);
    // فاتورة معلّقة مابتتحسبش
    PaymentInvoice::factory()->create(['bootcamp_id' => $math->getKey(), 'amount' => 999, 'status' => InvoiceStatus::Pending]);
    app(WalletAction::class)->deposit(Student::factory()->create(), 50, TransactionSource::RechargeCard);
    // إيداع من الإدارة مش كارت
    app(WalletAction::class)->deposit(Student::factory()->create(), 70, TransactionSource::Admin);

    $report = RevenueReportQuery::lastTwelveMonths();
    $totals = $report->totals();

    expect($totals['transfers'])->toBe(300.0)
        ->and($totals['card_redemptions'])->toBe(50.0)
        ->and($totals['wallet_purchases'])->toBe(150.0)
        ->and($totals['collected'])->toBe(350.0);

    $months = $report->byMonth();
    expect($months)->toHaveCount(12)
        ->and($months[0]['month'])->toBe(now()->format('Y-m'))
        ->and($months[0]['collected'])->toBe(350.0);

    $courses = collect($report->byCourse())->keyBy('name');
    expect($courses['Math']['total'])->toBe(350.0)
        ->and($courses['Math']['transfers'])->toBe(200.0)
        ->and($courses['Math']['wallet_purchases'])->toBe(150.0)
        ->and($courses['Physics']['total'])->toBe(100.0);

    $methods = collect($report->byMethod())->keyBy('key');
    expect($methods['method:'.$vodafone->getKey()]['total'])->toBe(300.0)
        ->and($methods['method:'.$vodafone->getKey()]['count'])->toBe(2)
        ->and($methods['cards']['total'])->toBe(50.0)
        ->and($methods['wallet']['total'])->toBe(150.0);
});

it('فلتر الفترة بيشيل اللي برّاها', function (): void {
    actingAsRole('admin');
    $course = Bootcamp::factory()->create();
    paidInvoice($course, 100, PaymentGateway::ManualTransfer, at: now()->subMonths(3));
    paidInvoice($course, 40, PaymentGateway::ManualTransfer);

    $page = Livewire::test(RevenueReport::class)->assertOk()->assertSee(__('billing::billing.report.by_month'));
    expect($page->instance()->report()->totals()['transfers'])->toBe(140.0);

    $page->set('from', now()->subMonth()->toDateString())->set('to', now()->toDateString());
    expect($page->instance()->report()->totals()['transfers'])->toBe(40.0);

    // من > إلى بيتبدّلوا بدل ما يطلع فاضي
    $page->set('from', now()->toDateString())->set('to', now()->subMonths(4)->toDateString());
    expect($page->instance()->report()->totals()['transfers'])->toBe(140.0);
});

it('التقرير مابيحسبش فلوس مؤسسة تانية', function (): void {
    actingAsRole('admin');
    $other = Tenant::factory()->create();
    app(TenantContext::class)->forTenant($other->getKey(), function (): void {
        paidInvoice(Bootcamp::factory()->create(), 500, PaymentGateway::ManualTransfer);
    });

    Livewire::test(RevenueReport::class)->assertOk();
    expect(RevenueReportQuery::lastTwelveMonths()->totals()['transfers'])->toBe(0.0);
});
