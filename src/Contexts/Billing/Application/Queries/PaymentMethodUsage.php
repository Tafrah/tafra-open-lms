<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;

/**
 * المقبول على كل رقم دفع في الشهر الحالي، مقارنةً بحده الشهري (لو محدد).
 * الرقم اللي وصل حده مابيظهرش للطالب في صفحة الدفع.
 */
final class PaymentMethodUsage
{
    /** @var array<int, float>|null */
    private ?array $memo = null;

    /**
     * الطرق اللي تظهر للطالب: مفعّلة ولسه ماوصلتش حدها.
     *
     * @return Collection<int, PaymentMethod>
     */
    public function availableForCheckout(): Collection
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->filter(fn (PaymentMethod $method): bool => $this->isAvailable($method))
            ->values();
    }

    public function isAvailable(PaymentMethod $method): bool
    {
        if (! $method->is_active) {
            return false;
        }

        return $method->monthly_limit === null || $this->usedThisMonth($method) < (float) $method->monthly_limit;
    }

    public function usedThisMonth(PaymentMethod $method): float
    {
        // استعلام واحد لكل الطرق — الجدول بيسأل لكل صف (مفيش استعلام لكل صف)
        $this->memo ??= $this->totals();

        return $this->memo[(int) $method->getKey()] ?? 0.0;
    }

    public function forget(): void
    {
        $this->memo = null;
    }

    /** @return array<int, float> */
    private function totals(): array
    {
        return PaymentReceipt::query()
            ->where('status', ReceiptStatus::Approved)
            ->whereNotNull('payment_method_id')
            ->whereHas('invoice', fn (Builder $invoice): Builder => $invoice
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()]))
            ->groupBy('payment_method_id')
            ->selectRaw('payment_method_id, SUM(amount) as total')
            ->pluck('total', 'payment_method_id')
            ->mapWithKeys(fn ($total, $id): array => [(int) $id => (float) $total])
            ->all();
    }
}
