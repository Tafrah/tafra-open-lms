<?php

declare(strict_types=1);

namespace App\Filament\Shell;

use Filament\Facades\Filament;
use FutureCode\Onboarding\Facades\Onboarding;
use Illuminate\Support\Facades\Storage;
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\EducationalLevelResource;
use Src\Contexts\Academy\Presentation\Filament\Resources\OnlineLessonResource;
use Src\Contexts\Billing\Presentation\Filament\Pages\RevenueReport;
use Src\Contexts\Billing\Presentation\Filament\Resources\CouponResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentInvoiceResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\PaymentMethodResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlineExamResource;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;
use Src\Contexts\Storefront\Application\Queries\SiteSettings;
use Src\Contexts\Storefront\Presentation\Filament\Pages\ManageSiteSettings;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource;
use Src\Contexts\Transfer\Presentation\Filament\Pages\DataTransfer;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Presentation\Filament\Pages\Dashboard;
use Throwable;

/**
 * بيانات غلاف لوحة التحكم بشكل «طفرة البسيط» (LMS-Admin-ReactJS → themes/simple):
 * اسم ولوجو الأكاديمية، روابط الهيدر السريعة، مربعات «إدارة المنصة»، وتقدّم المرشد.
 *
 * كل رابط بيظهر بس لو المستخدم يقدر يفتح صفحته — نفس قاعدة السايدبار والـ Policies.
 */
final class AdminShell
{
    /** @return array{name: string, logo: string|null} */
    public static function brand(): array
    {
        $tenant = Filament::getTenant();
        $name = (string) ($tenant?->getAttribute('name') ?? config('app.name'));
        $logo = null;

        try {
            $site = app(SiteSettings::class)->load($name);
            $name = $site->platformName;
            $logo = $site->logoUrl;
        } catch (Throwable) {
            // قبل الإعدادات (أول تشغيل) — الاسم من المؤسسة
        }

        if ($logo === null && filled($path = $tenant?->getAttribute('logo_path'))) {
            $logo = Storage::disk(app(DiskResolver::class)->for('branding'))->url((string) $path);
        }

        return ['name' => $name, 'logo' => $logo];
    }

    public static function portalUrl(): string
    {
        return route('site.home');
    }

    /**
     * روابط الهيدر (زي MenuInner في طفرة): نظرة عامة، إضافة طالب، الكورسات، الإيصالات، إعلان.
     *
     * @return list<array{label: string, url: string, active: bool, badge: int|null}>
     */
    public static function quickLinks(): array
    {
        $current = (string) request()->url();
        $exact = static fn (string $href): bool => $current === $href;
        $prefix = static fn (string $href): bool => str_starts_with($current, $href);
        $never = static fn (string $href): bool => false;

        // [المفتاح، مسموح؟، الرابط، إمتى يبقى «الحالي»، عدّاد]
        $links = [
            ['overview', true, fn (): string => Dashboard::getUrl(), $exact, null],
            ['add_student', StudentResource::canCreate(), fn (): string => StudentResource::getUrl('create'), $never, null],
            ['courses', BootcampResource::canViewAny(), fn (): string => BootcampResource::getUrl(), $prefix, null],
            ['receipts', PaymentInvoiceResource::canViewAny(), fn (): string => PaymentInvoiceResource::getUrl(), $prefix, self::pendingReceipts()],
            ['announce', AnnouncementResource::canCreate(), fn (): string => AnnouncementResource::getUrl('create'), $never, null],
        ];

        $out = [];

        foreach ($links as [$key, $allowed, $url, $isCurrent, $badge]) {
            if (! $allowed) {
                continue;
            }

            $href = $url();
            $out[] = [
                'label' => __("shell.topbar.{$key}"),
                'url' => $href,
                'active' => $isCurrent($href),
                'badge' => $badge,
            ];
        }

        return $out;
    }

    /**
     * مربعات «إدارة المنصة» (PlatformShortcuts في طفرة) — بنفس ألوانها حسب المنطقة.
     *
     * @return list<array{key: string, label: string, url: string, icon: string, tone: string, badge: int|null}>
     */
    public static function shortcuts(): array
    {
        $tiles = [
            // الشغل اليومي الأول: الكارت بيعرض أول صفين لحد «عرض الكل»
            ['students', 'phosphor-student-bold', 'blue', StudentResource::class],
            ['courses', 'phosphor-monitor-play-bold', 'violet', BootcampResource::class],
            ['exams', 'phosphor-exam-bold', 'violet', OnlineExamResource::class],
            ['invoices', 'phosphor-receipt-bold', 'green', PaymentInvoiceResource::class],
            ['attempts', 'phosphor-clipboard-text-bold', 'violet', OnlinePassedExamResource::class],
            ['lessons', 'phosphor-book-open-bold', 'violet', OnlineLessonResource::class],
            ['enrollments', 'phosphor-user-check-bold', 'teal', EnrollmentResource::class],
            ['cards', 'phosphor-credit-card-bold', 'green', RechargeCardGroupResource::class],
            ['coupons', 'phosphor-ticket-bold', 'green', CouponResource::class],
            ['transactions', 'phosphor-currency-circle-dollar-bold', 'green', MoneyTransactionResource::class],
            ['methods', 'phosphor-wallet-bold', 'green', PaymentMethodResource::class],
            ['report', 'phosphor-chart-line-up-bold', 'blue', RevenueReport::class],
            ['levels', 'phosphor-graduation-cap-bold', 'blue', EducationalLevelResource::class],
            ['announcements', 'phosphor-megaphone-bold', 'amber', AnnouncementResource::class],
            ['users', 'phosphor-chalkboard-teacher-bold', 'blue', UserResource::class],
            ['site', 'phosphor-storefront-bold', 'orange', ManageSiteSettings::class],
            ['transfer', 'phosphor-arrows-left-right-bold', 'pink', DataTransfer::class],
        ];

        $out = [];

        foreach ($tiles as [$key, $icon, $tone, $target]) {
            $allowed = method_exists($target, 'canViewAny') ? $target::canViewAny() : $target::canAccess();

            if (! $allowed) {
                continue;
            }

            $out[] = [
                'key' => $key,
                'label' => __("shell.shortcuts.tiles.{$key}"),
                'url' => $target::getUrl(),
                'icon' => $icon,
                'tone' => $tone,
                'badge' => $key === 'invoices' ? self::pendingReceipts() : null,
            ];
        }

        return $out;
    }

    /** @return array{done: int, total: int}|null «مرشدك 3/7» — خطوات البداية */
    public static function guide(): ?array
    {
        try {
            $user = auth()->user();
            $steps = Onboarding::stepsFor($user);

            if ($steps->isEmpty()) {
                return null;
            }

            $done = $steps->filter(fn ($step): bool => $step->isComplete())->count();

            return ['done' => $done, 'total' => $steps->count()];
        } catch (Throwable) {
            return null;
        }
    }

    public static function announcementsUrl(): ?string
    {
        return AnnouncementResource::canViewAny() ? AnnouncementResource::getUrl() : null;
    }

    private static function pendingReceipts(): ?int
    {
        $badge = PaymentInvoiceResource::getNavigationBadge();

        return $badge !== null && (int) $badge > 0 ? (int) $badge : null;
    }
}
