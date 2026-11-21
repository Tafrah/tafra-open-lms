<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Presentation\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource;
use Src\Support\Application\Contracts\TenantContext;

/** أول حاجة المدرس بيشوفها: الطلبة، دخل الشهر، الإيصالات والمقالي اللي مستنياه. */
final class LmsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return Gate::allows('widget.lms_overview');
    }

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $tenantId = app(TenantContext::class)->id();

        $stats = Cache::remember("widget:lms-overview:{$tenantId}", now()->addMinutes(2), fn (): array => [
            'students' => Student::query()->count(),
            'new_students' => Student::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'revenue' => (float) PaymentInvoice::query()->where('status', InvoiceStatus::Paid)
                ->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'pending_receipts' => PaymentInvoice::query()->where('status', InvoiceStatus::Pending)->whereHas('receipts')->count(),
            'pending_essays' => OnlinePassedExam::query()->where('finished', true)->where('grading_status', GradingStatus::Pending)->count(),
        ]);

        return [
            Stat::make(__('students::students.widget.students'), fc_number($stats['students']))
                ->description(__('students::students.widget.new_students', ['count' => fc_number($stats['new_students'])]))
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('primary')
                ->url(StudentResource::getUrl('index')),
            Stat::make(__('students::students.widget.revenue'), fc_number($stats['revenue']).' '.__('academy::academy.currency'))
                ->description(__('students::students.widget.revenue_hint'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make(__('students::students.widget.pending_receipts'), fc_number($stats['pending_receipts']))
                ->description(__('students::students.widget.pending_receipts_hint'))
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color($stats['pending_receipts'] > 0 ? 'danger' : 'gray')
                ->url(PaymentInvoiceResource::getUrl('index')),
            Stat::make(__('students::students.widget.pending_essays'), fc_number($stats['pending_essays']))
                ->description(__('students::students.widget.pending_essays_hint'))
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color($stats['pending_essays'] > 0 ? 'warning' : 'gray')
                ->url(OnlinePassedExamResource::getUrl('index')),
        ];
    }
}
