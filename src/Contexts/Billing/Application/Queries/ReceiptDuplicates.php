<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;

/**
 * كشف الإيصالات المكررة — تحذير للمراجع، مش رفض تلقائي:
 *  - نفس الصورة (sha256) على فاتورة تانية، في أي وقت.
 *  - نفس رقم العملية على فاتورة تانية، في أي وقت (رقم العملية مابيتكررش).
 *  - نفس رقم المحوِّل + نفس المبلغ + نفس اليوم على فاتورة تانية.
 */
final class ReceiptDuplicates
{
    public const SIGNAL_IMAGE = 'same_image';

    public const SIGNAL_REFERENCE = 'same_reference';

    public const SIGNAL_SENDER = 'same_sender_amount_day';

    /**
     * التطابقات لفاتورة واحدة (صفحة المراجعة).
     *
     * @return list<array{signal: string, invoice_id: int, number: string}>
     */
    public function forInvoice(PaymentInvoice $invoice): array
    {
        $matches = [];

        foreach ($invoice->receipts as $receipt) {
            foreach ($this->signals($receipt) as $signal => $query) {
                $others = $query
                    ->where('payment_invoice_id', '!=', $invoice->getKey())
                    ->with('invoice:id,number')
                    ->limit(20)
                    ->get();

                foreach ($others as $other) {
                    $key = $signal.':'.$other->payment_invoice_id;
                    $matches[$key] ??= [
                        'signal' => $signal,
                        'invoice_id' => (int) $other->payment_invoice_id,
                        'number' => (string) ($other->invoice->number ?? '#'.$other->payment_invoice_id),
                    ];
                }
            }
        }

        return array_values($matches);
    }

    /**
     * عمود `fraud_flagged` على استعلام الفواتير — subquery واحد للصفحة كلها
     * (مش استعلام لكل صف). بيشتغل على MySQL وPostgres.
     *
     * @template TModel of Model
     *
     * @param Builder<TModel> $invoices
     * @return Builder<TModel>
     */
    public static function flag(Builder $invoices): Builder
    {
        return $invoices->withExists(['receipts as fraud_flagged' => fn (Builder $receipts) => self::duplicated($receipts)]);
    }

    /**
     * فلتر «فيها إيصال متكرر».
     *
     * @template TModel of Model
     *
     * @param Builder<TModel> $invoices
     * @return Builder<TModel>
     */
    public static function whereFlagged(Builder $invoices): Builder
    {
        return $invoices->whereHas('receipts', fn (Builder $receipts) => self::duplicated($receipts));
    }

    /**
     * @template TReceipt of Model
     *
     * @param Builder<TReceipt> $receipts
     * @return Builder<TReceipt>
     */
    private static function duplicated(Builder $receipts): Builder
    {
        return $receipts->whereExists(function (QueryBuilder $dup): void {
            $dup->selectRaw('1')
                ->from('payment_receipts as dup')
                ->whereColumn('dup.tenant_id', 'payment_receipts.tenant_id')
                ->whereColumn('dup.payment_invoice_id', '<>', 'payment_receipts.payment_invoice_id')
                ->where(function (QueryBuilder $match): void {
                    $match
                        ->where(fn (QueryBuilder $q) => $q
                            ->whereNotNull('payment_receipts.file_hash')
                            ->whereColumn('dup.file_hash', 'payment_receipts.file_hash'))
                        ->orWhere(fn (QueryBuilder $q) => $q
                            ->whereNotNull('payment_receipts.transfer_reference')
                            ->where('payment_receipts.transfer_reference', '<>', '')
                            ->whereColumn('dup.transfer_reference', 'payment_receipts.transfer_reference'))
                        ->orWhere(fn (QueryBuilder $q) => $q
                            ->whereNotNull('payment_receipts.sender_phone')
                            ->where('payment_receipts.sender_phone', '<>', '')
                            ->whereColumn('dup.sender_phone', 'payment_receipts.sender_phone')
                            ->whereColumn('dup.amount', 'payment_receipts.amount')
                            ->whereRaw('DATE(dup.created_at) = DATE(payment_receipts.created_at)'));
                });
        });
    }

    /** @return array<string, Builder<PaymentReceipt>> */
    private function signals(PaymentReceipt $receipt): array
    {
        $signals = [];

        if (filled($receipt->file_hash)) {
            $signals[self::SIGNAL_IMAGE] = PaymentReceipt::query()->where('file_hash', $receipt->file_hash);
        }

        if (filled($receipt->transfer_reference)) {
            $signals[self::SIGNAL_REFERENCE] = PaymentReceipt::query()->where('transfer_reference', $receipt->transfer_reference);
        }

        if (filled($receipt->sender_phone) && $receipt->created_at !== null) {
            $signals[self::SIGNAL_SENDER] = PaymentReceipt::query()
                ->where('sender_phone', $receipt->sender_phone)
                ->where('amount', $receipt->amount)
                ->whereDate('created_at', $receipt->created_at->toDateString());
        }

        return $signals;
    }
}
