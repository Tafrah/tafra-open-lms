<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Application\Contracts\DiskResolver;

/**
 * الطالب بيرفع صورة إيصال التحويل على فاتورة معلّقة. لو كان فيه إيصال
 * مستني مراجعة، الجديد بيحل محله (غلط في الصورة مثلًا).
 */
final readonly class SubmitReceiptAction
{
    public function __construct(private DiskResolver $disks) {}

    public function handle(
        Student $student,
        PaymentInvoice $invoice,
        PaymentMethod $method,
        UploadedFile $image,
        ?string $senderPhone = null,
        ?string $reference = null,
    ): PaymentReceipt {
        throw_unless((int) $invoice->student_id === (int) $student->getKey(), BillingException::because('invoice_not_yours'));
        throw_unless($invoice->status === InvoiceStatus::Pending, BillingException::because('invoice_closed'));
        throw_unless($invoice->payment_gateway === PaymentGateway::ManualTransfer, BillingException::because('invoice_not_manual'));
        throw_unless($method->is_active, BillingException::because('method_inactive'));

        return DB::transaction(function () use ($student, $invoice, $method, $image, $senderPhone, $reference): PaymentReceipt {
            $receipt = PaymentReceipt::query()
                ->where('payment_invoice_id', $invoice->getKey())
                ->where('status', ReceiptStatus::Pending)
                ->first() ?? new PaymentReceipt([
                    'payment_invoice_id' => $invoice->getKey(),
                    'student_id' => $student->getKey(),
                ]);

            $receipt->fill([
                'payment_method_id' => $method->getKey(),
                'amount' => $invoice->amount,
                'sender_phone' => $senderPhone,
                'transfer_reference' => $reference,
                'status' => ReceiptStatus::Pending,
            ])->save();

            // اسم المجموعة `receipt` (مفرد) — هو اللي في `storage.private_collections`؛
            // `receipts` كان بيرجّع الديسك العام فصور الإيصالات كانت بتتحفظ public
            $receipt->addMedia($image)->toMediaCollection('receipt', $this->disks->for('receipt'));

            return $receipt;
        });
    }
}
