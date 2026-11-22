<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Queries;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Enums\TransactionType;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;

/**
 * تقرير الدخل في فترة. الفلوس بتتسجّل في ٣ أماكن، وكل واحد سطر لوحده:
 *  - التحويلات: فواتير `manual_transfer` اتقبلت (`paid_at`) — فلوس دخلت على أرقامك.
 *  - كروت الشحن: إيداع `recharge_card` في `money_transactions` — فلوس دخلت لما الكارت اتباع.
 *  - الشراء بالمحفظة: فواتير `wallet` مدفوعة — صرف من رصيد اتحسب قبل كده (كارت أو إيداع)،
 *    فبيظهر لوحده ومابيدخلش في «اللي اتحصّل» عشان الجنيه مايتعدّش مرتين.
 * كل الاستعلامات Eloquent (سكوب المؤسسة شغال)، والتجميع بالشهر بدالة حسب القاعدة.
 */
final class RevenueReport
{
    public function __construct(
        private readonly CarbonImmutable $from,
        private readonly CarbonImmutable $to,
    ) {}

    public static function between(CarbonImmutable $from, CarbonImmutable $to): self
    {
        return new self($from->startOfDay(), $to->endOfDay());
    }

    public static function lastTwelveMonths(): self
    {
        $now = CarbonImmutable::now();

        return self::between($now->subMonths(11)->startOfMonth(), $now);
    }

    public function from(): CarbonImmutable
    {
        return $this->from;
    }

    public function to(): CarbonImmutable
    {
        return $this->to;
    }

    /** @return array{transfers: float, wallet_purchases: float, card_redemptions: float, collected: float, paid_invoices: int} */
    public function totals(): array
    {
        $transfers = (float) $this->paidInvoices(PaymentGateway::ManualTransfer)->sum('amount');
        $cards = (float) $this->cardRedemptions()->sum('amount');

        return [
            'transfers' => round($transfers, 2),
            'wallet_purchases' => round((float) $this->paidInvoices(PaymentGateway::Wallet)->sum('amount'), 2),
            'card_redemptions' => round($cards, 2),
            'collected' => round($transfers + $cards, 2),
            'paid_invoices' => $this->paidInvoices()->count(),
        ];
    }

    /**
     * كل شهر في الفترة (حتى الشهور الفاضية) بالأسطر التلاتة.
     *
     * @return list<array{month: string, transfers: float, wallet_purchases: float, card_redemptions: float, collected: float}>
     */
    public function byMonth(): array
    {
        $transfers = $this->monthly($this->paidInvoices(PaymentGateway::ManualTransfer), 'paid_at');
        $wallet = $this->monthly($this->paidInvoices(PaymentGateway::Wallet), 'paid_at');
        $cards = $this->monthly($this->cardRedemptions(), 'created_at');

        $rows = [];

        foreach (CarbonPeriod::create($this->from->startOfMonth(), '1 month', $this->to->startOfMonth()) as $month) {
            $key = $month->format('Y-m');
            $rows[] = [
                'month' => $key,
                'transfers' => $transfers[$key] ?? 0.0,
                'wallet_purchases' => $wallet[$key] ?? 0.0,
                'card_redemptions' => $cards[$key] ?? 0.0,
                'collected' => round(($transfers[$key] ?? 0.0) + ($cards[$key] ?? 0.0), 2),
            ];
        }

        return array_reverse($rows);
    }

    /**
     * الفواتير المدفوعة لكل كورس (تحويل + محفظة؛ المجاني صفر ومابيظهرش).
     *
     * @return list<array{bootcamp_id: int|null, name: string, count: int, transfers: float, wallet_purchases: float, total: float}>
     */
    public function byCourse(): array
    {
        $rows = $this->paidInvoices()
            ->where('amount', '>', 0)
            ->groupBy('bootcamp_id', 'payment_gateway')
            ->selectRaw('bootcamp_id, payment_gateway, COUNT(*) as invoices, SUM(amount) as total')
            ->toBase()
            ->get();

        $names = Bootcamp::query()->whereIn('id', $rows->pluck('bootcamp_id')->filter()->unique()->all())->pluck('name', 'id');
        $courses = [];

        foreach ($rows as $row) {
            $id = $row->bootcamp_id !== null ? (int) $row->bootcamp_id : null;
            $key = (string) ($id ?? 0);
            $courses[$key] ??= [
                'bootcamp_id' => $id,
                'name' => $id !== null ? (string) ($names[$id] ?? '#'.$id) : '—',
                'count' => 0,
                'transfers' => 0.0,
                'wallet_purchases' => 0.0,
                'total' => 0.0,
            ];

            $amount = round((float) $row->total, 2);
            $courses[$key]['count'] += (int) $row->invoices;
            $courses[$key]['total'] = round($courses[$key]['total'] + $amount, 2);

            if ($row->payment_gateway === PaymentGateway::ManualTransfer->value) {
                $courses[$key]['transfers'] = round($courses[$key]['transfers'] + $amount, 2);
            } elseif ($row->payment_gateway === PaymentGateway::Wallet->value) {
                $courses[$key]['wallet_purchases'] = round($courses[$key]['wallet_purchases'] + $amount, 2);
            }
        }

        $courses = array_values($courses);
        usort($courses, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $courses;
    }

    /**
     * التحويلات المقبولة لكل رقم دفع، وبعدها سطر كروت الشحن وسطر الشراء بالمحفظة.
     *
     * @return list<array{key: string, name: string, detail: string|null, count: int, total: float}>
     */
    public function byMethod(): array
    {
        $rows = PaymentReceipt::query()
            ->where('status', ReceiptStatus::Approved)
            ->whereHas('invoice', fn (Builder $invoice): Builder => $invoice
                ->where('status', InvoiceStatus::Paid)
                ->whereBetween('paid_at', [$this->from, $this->to]))
            ->groupBy('payment_method_id')
            ->selectRaw('payment_method_id, COUNT(*) as receipts, SUM(amount) as total')
            ->toBase()
            ->get();

        $methods = PaymentMethod::query()->withTrashed()
            ->whereIn('id', $rows->pluck('payment_method_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $lines = [];

        foreach ($rows as $row) {
            $method = $row->payment_method_id !== null ? $methods->get((int) $row->payment_method_id) : null;
            $lines[] = [
                'key' => 'method:'.($row->payment_method_id ?? 0),
                'name' => $method instanceof PaymentMethod ? (string) $method->name : __('billing::billing.report.unknown_method'),
                'detail' => $method instanceof PaymentMethod ? $method->type->getLabel().' · '.$method->account_number : null,
                'count' => (int) $row->receipts,
                'total' => round((float) $row->total, 2),
            ];
        }

        usort($lines, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        $lines[] = [
            'key' => 'cards',
            'name' => __('billing::billing.report.lines.card_redemptions'),
            'detail' => null,
            'count' => $this->cardRedemptions()->count(),
            'total' => round((float) $this->cardRedemptions()->sum('amount'), 2),
        ];
        $lines[] = [
            'key' => 'wallet',
            'name' => __('billing::billing.report.lines.wallet_purchases'),
            'detail' => __('billing::billing.report.wallet_note'),
            'count' => $this->paidInvoices(PaymentGateway::Wallet)->count(),
            'total' => round((float) $this->paidInvoices(PaymentGateway::Wallet)->sum('amount'), 2),
        ];

        return $lines;
    }

    /** @return Builder<PaymentInvoice> */
    private function paidInvoices(?PaymentGateway $gateway = null): Builder
    {
        return PaymentInvoice::query()
            ->where('status', InvoiceStatus::Paid)
            ->whereBetween('paid_at', [$this->from, $this->to])
            ->when($gateway !== null, fn (Builder $q): Builder => $q->where('payment_gateway', $gateway));
    }

    /** @return Builder<MoneyTransaction> */
    private function cardRedemptions(): Builder
    {
        return MoneyTransaction::query()
            ->where('type', TransactionType::Deposit)
            ->where('source', TransactionSource::RechargeCard)
            ->whereBetween('created_at', [$this->from, $this->to]);
    }

    /**
     * @param Builder<PaymentInvoice>|Builder<MoneyTransaction> $query
     * @return array<string, float> «YYYY-MM» ← المجموع
     */
    private function monthly(Builder $query, string $column): array
    {
        $month = self::monthExpression($column);

        return $query
            ->selectRaw("{$month} as month, SUM(amount) as total")
            ->groupByRaw($month)
            ->toBase()
            ->pluck('total', 'month')
            ->mapWithKeys(fn ($total, $key): array => [(string) $key => round((float) $total, 2)])
            ->all();
    }

    /** العمود ثابت من الكود (مش من المستخدم) — آمن في selectRaw */
    private static function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
