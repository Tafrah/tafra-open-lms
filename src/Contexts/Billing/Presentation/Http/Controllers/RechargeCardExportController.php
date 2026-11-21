<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * طباعة كروت الدفعة (صفحة A4 جاهزة للطباعة) أو تنزيلها CSV يفتح في Excel.
 * الكود الخام مايظهرش غير هنا — والدخول بصلاحية `print.recharge_cards`.
 */
final class RechargeCardExportController
{
    public function print(string $group): View
    {
        $group = $this->group($group);

        return view('billing::cards.print', [
            'group' => $group,
            'cards' => $group->cards()->whereNull('used_at')->where('is_active', true)->orderBy('id')->pluck('code'),
            'url' => (string) config('app.url'),
        ]);
    }

    public function csv(string $group): StreamedResponse
    {
        $group = $this->group($group);

        $filename = 'cards-'.$group->getKey().'.csv';

        return response()->streamDownload(function () use ($group): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            // BOM عشان Excel يقرا العربي صح
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('billing::billing.card.fields.code'),
                __('billing::billing.card_group.fields.value'),
                __('billing::billing.card.fields.used_at'),
            ]);

            $value = $group->valueLabel();

            $group->cards()->orderBy('id')->chunk(1000, function ($cards) use ($out, $value): void {
                foreach ($cards as $card) {
                    fputcsv($out, [$card->code, $value, $card->used_at?->format('Y-m-d H:i') ?? '']);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * الربط يدوي مش route binding: الـ binding بيحصل قبل ما سياق المؤسسة يتضبط،
     * والاستعلام من غير سياق بيرمي (BelongsToTenant).
     */
    private function group(string $id): RechargeCardGroup
    {
        $group = RechargeCardGroup::query()->with('bootcamp:id,name')->findOrFail((int) $id);

        Gate::authorize('print', $group);

        return $group;
    }
}
