<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Billing\Application\Actions\SubmitReceiptAction;
use Src\Contexts\Billing\Application\Queries\ReceiptDuplicates;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Pages\ListPaymentInvoices;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource\Pages\ViewPaymentInvoice;
use Src\Contexts\Students\Domain\Models\Student;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::fake('public');
});

function fraudInvoice(): PaymentInvoice
{
    $student = Student::factory()->create();

    return PaymentInvoice::factory()->create(['student_id' => $student->getKey(), 'amount' => 150]);
}

it('رفع الإيصال بيحفظ بصمة sha256 للملف', function (): void {
    inTenant();
    $invoice = fraudInvoice();
    $image = UploadedFile::fake()->image('r.png', 20, 20);
    $bytes = (string) file_get_contents($image->getRealPath());
    $file = UploadedFile::fake()->createWithContent('r.png', $bytes);

    $receipt = app(SubmitReceiptAction::class)->handle($invoice->student, $invoice, PaymentMethod::factory()->create(), $file, '01000000000');

    expect($receipt->file_hash)->toBe(hash('sha256', $bytes));
});

it('نفس الصورة على فاتورتين = تحذير على الاتنين، ومفيش رفض تلقائي', function (): void {
    inTenant();
    $method = PaymentMethod::factory()->create();
    $first = fraudInvoice();
    $second = fraudInvoice();

    $image = UploadedFile::fake()->image('a.png', 30, 30);
    $bytes = (string) file_get_contents($image->getRealPath());

    app(SubmitReceiptAction::class)->handle($first->student, $first, $method, UploadedFile::fake()->createWithContent('a.png', $bytes), '01011111111');
    app(SubmitReceiptAction::class)->handle($second->student, $second, $method, UploadedFile::fake()->createWithContent('b.png', $bytes), '01022222222');

    $matches = app(ReceiptDuplicates::class)->forInvoice($second->fresh());

    expect($matches)->toHaveCount(1)
        ->and($matches[0]['signal'])->toBe(ReceiptDuplicates::SIGNAL_IMAGE)
        ->and($matches[0]['number'])->toBe($first->number)
        ->and($second->fresh()->status)->toBe(InvoiceStatus::Pending);
});

it('نفس رقم العملية، أو نفس المحوِّل والمبلغ في نفس اليوم = تحذير', function (): void {
    inTenant();
    $a = fraudInvoice();
    $b = fraudInvoice();
    $c = fraudInvoice();
    $d = fraudInvoice();
    PaymentReceipt::factory()->create(['payment_invoice_id' => $a->getKey(), 'student_id' => $a->student_id, 'transfer_reference' => 'TX-1', 'sender_phone' => '01000000001']);
    PaymentReceipt::factory()->create(['payment_invoice_id' => $b->getKey(), 'student_id' => $b->student_id, 'transfer_reference' => 'TX-1', 'sender_phone' => '01000000002']);
    PaymentReceipt::factory()->create(['payment_invoice_id' => $c->getKey(), 'student_id' => $c->student_id, 'sender_phone' => '01000000009', 'amount' => 150]);
    PaymentReceipt::factory()->create(['payment_invoice_id' => $d->getKey(), 'student_id' => $d->student_id, 'sender_phone' => '01000000009', 'amount' => 150]);

    expect(array_column(app(ReceiptDuplicates::class)->forInvoice($b), 'signal'))->toBe([ReceiptDuplicates::SIGNAL_REFERENCE])
        ->and(array_column(app(ReceiptDuplicates::class)->forInvoice($d), 'signal'))->toBe([ReceiptDuplicates::SIGNAL_SENDER]);
});

it('نفس المحوِّل بمبلغ تاني أو في يوم تاني مش تحذير', function (): void {
    inTenant();
    $a = fraudInvoice();
    $b = fraudInvoice();
    $c = fraudInvoice();
    PaymentReceipt::factory()->create(['payment_invoice_id' => $a->getKey(), 'student_id' => $a->student_id, 'sender_phone' => '01000000009', 'amount' => 150]);
    PaymentReceipt::factory()->create(['payment_invoice_id' => $b->getKey(), 'student_id' => $b->student_id, 'sender_phone' => '01000000009', 'amount' => 200]);
    $old = PaymentReceipt::factory()->create(['payment_invoice_id' => $c->getKey(), 'student_id' => $c->student_id, 'sender_phone' => '01000000009', 'amount' => 150]);
    $old->forceFill(['created_at' => now()->subDays(3)])->save();

    expect(app(ReceiptDuplicates::class)->forInvoice($b))->toBe([])
        ->and(app(ReceiptDuplicates::class)->forInvoice($c->fresh()))->toBe([]);
});

it('الجدول بيعلّم الفاتورة المشكوك فيها، والفلتر بيجيبها', function (): void {
    actingAsRole('admin');
    $a = fraudInvoice();
    $b = fraudInvoice();
    $clean = fraudInvoice();
    PaymentReceipt::factory()->create(['payment_invoice_id' => $a->getKey(), 'student_id' => $a->student_id, 'file_hash' => str_repeat('a', 64)]);
    PaymentReceipt::factory()->create(['payment_invoice_id' => $b->getKey(), 'student_id' => $b->student_id, 'file_hash' => str_repeat('a', 64)]);
    PaymentReceipt::factory()->create(['payment_invoice_id' => $clean->getKey(), 'student_id' => $clean->student_id, 'file_hash' => str_repeat('b', 64)]);

    Livewire::test(ListPaymentInvoices::class)
        ->loadTable()
        ->assertTableColumnStateSet('fraud_flagged', true, $a)
        ->assertTableColumnStateSet('fraud_flagged', false, $clean)
        ->filterTable('fraud_flagged')
        ->assertCanSeeTableRecords([$a, $b])
        ->assertCanNotSeeTableRecords([$clean]);
});

it('صفحة المراجعة بتعرض التحذير برقم الفاتورة التانية', function (): void {
    actingAsRole('admin');
    $a = fraudInvoice();
    $b = fraudInvoice();
    PaymentReceipt::factory()->create(['payment_invoice_id' => $a->getKey(), 'student_id' => $a->student_id, 'transfer_reference' => 'REF-77']);
    PaymentReceipt::factory()->create(['payment_invoice_id' => $b->getKey(), 'student_id' => $b->student_id, 'transfer_reference' => 'REF-77']);

    Livewire::test(ViewPaymentInvoice::class, ['record' => $b->getKey()])
        ->assertOk()
        ->assertSee(__('billing::billing.invoice.fraud.heading'))
        ->assertSee($a->number);

    $clean = fraudInvoice();
    Livewire::test(ViewPaymentInvoice::class, ['record' => $clean->getKey()])
        ->assertOk()
        ->assertDontSee(__('billing::billing.invoice.fraud.heading'));
});
