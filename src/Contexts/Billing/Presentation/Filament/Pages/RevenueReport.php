<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Presentation\Filament\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Src\Contexts\Billing\Application\Queries\RevenueReport as RevenueReportQuery;
use Src\Support\Presentation\Filament\Navigation\NavigationGroup;
use Throwable;

/**
 * «تقرير الدخل» — الدخل بالشهر، بالكورس، وبطريقة الدفع في فترة يختارها المدرس.
 * للمدير بس (`access.revenue_report`) — المساعد بيراجع إيصالات مش بيشوف الأرباح.
 */
final class RevenueReport extends Page
{
    /** أقصى فترة — جدول الشهور مايبقاش آلاف الصفوف */
    private const MAX_YEARS = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 6;

    protected string $view = 'billing::filament.revenue-report';

    public ?string $from = null;

    public ?string $to = null;

    public static function getSlug(?Panel $panel = null): string
    {
        return 'revenue-report';
    }

    public static function canAccess(): bool
    {
        return Gate::allows('access.revenue_report');
    }

    public static function getNavigationGroup(): string
    {
        return NavigationGroup::Finance->getLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('billing::billing.report.title');
    }

    public function getTitle(): string
    {
        return __('billing::billing.report.title');
    }

    public function getSubheading(): string
    {
        return __('billing::billing.report.description');
    }

    public function mount(): void
    {
        $default = RevenueReportQuery::lastTwelveMonths();
        $this->from = $default->from()->toDateString();
        $this->to = $default->to()->toDateString();
    }

    public function resetRange(): void
    {
        $this->mount();
    }

    #[Computed]
    public function report(): RevenueReportQuery
    {
        $from = self::date($this->from) ?? RevenueReportQuery::lastTwelveMonths()->from();
        $to = self::date($this->to) ?? CarbonImmutable::now();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->lessThan($to->subYears(self::MAX_YEARS))) {
            $from = $to->subYears(self::MAX_YEARS);
        }

        return RevenueReportQuery::between($from, $to);
    }

    private static function date(?string $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value) ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
