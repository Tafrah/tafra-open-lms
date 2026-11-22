<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;
use Src\Contexts\Storefront\Application\Queries\SiteSettings;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Application\Contracts\TenantContext;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * طباعة كروت الدفعة (صفحة A4 جاهزة للطباعة) أو تنزيلها CSV يفتح في Excel.
 * الكود الخام مايظهرش غير هنا — والدخول بصلاحية `print.recharge_cards`.
 */
final readonly class RechargeCardExportController
{
    public function __construct(private DiskResolver $disks) {}

    public function print(string $group): View
    {
        $group = $this->group($group);

        // المسلسل = ترتيب الكارت في الدفعة كلها (مش بس اللي لسه ماتستخدمش) — ثابت مهما اتطبع كام مرة
        $positions = $group->cards()->orderBy('id')->pluck('id')->flip();

        $cards = $group->cards()->whereNull('used_at')->where('is_active', true)->orderBy('id')->get(['id', 'code'])
            ->map(fn (RechargeCard $card): array => [
                'code' => (string) $card->code,
                'serial' => self::serial($group, ((int) $positions->get($card->getKey(), 0)) + 1),
            ]);

        [$logoUrl, $platformName] = $this->branding();

        return view('billing::cards.print', [
            'group' => $group,
            'cards' => $cards,
            'url' => (string) config('app.url'),
            'logoUrl' => $logoUrl,
            'platformName' => $platformName,
        ]);
    }

    public static function serial(RechargeCardGroup $group, int $position): string
    {
        return $group->getKey().'-'.str_pad((string) $position, 4, '0', STR_PAD_LEFT);
    }

    /**
     * لوجو الأكاديمية: اللوجو اللي المدرس رفعه في إعدادات الموقع (لو موجود)، وإلا
     * `tenants.logo_path` بنفس منطق `storefront::partials.logo`، وإلا اسم المنصة كنص.
     *
     * @return array{0: string|null, 1: string}
     */
    private function branding(): array
    {
        $tenant = Tenant::query()->find(app(TenantContext::class)->id());
        $site = app(SiteSettings::class)->load($tenant !== null ? (string) $tenant->getTranslation('name', app()->getLocale(), true) : null);

        // `logoUrl` جاي من main (إعداد «اللوجو» في الموقع) — الفحص عشان الفرع يشتغل قبل الدمج وبعده
        $candidate = get_object_vars($site)['logoUrl'] ?? null;
        $logoUrl = is_string($candidate) && $candidate !== '' ? $candidate : null;

        if ($logoUrl === null && filled($tenant?->logo_path)) {
            $logoUrl = Storage::disk($this->disks->for('branding'))->url((string) $tenant->logo_path);
        }

        return [$logoUrl, $site->platformName];
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

        Gate::authorize('access.panel.admin');
        Gate::authorize('print', $group);

        return $group;
    }
}
