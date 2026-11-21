<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use FutureCode\Onboarding\Contracts\OnboardingStep;
use FutureCode\Onboarding\Facades\Onboarding;
use FutureCode\Onboarding\Models\OnboardingProgress;
use FutureCode\Onboarding\Tours\Tour;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * مقاييس الأونبوردنج.
 *
 * أهم رقم في الصفحة دي هو **الخطوة اللي أكتر ناس بتقف عندها** —
 * لو ٧٠٪ واقفين عند خطوة، الخطوة مش واضحة مش المستخدمين غلط
 * (docs/15 بند ٧).
 */
final class OnboardingAnalytics extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?int $navigationSort = 99;

    protected string $view = 'fc-onboarding::analytics';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'onboarding-analytics';
    }

    public static function getNavigationLabel(): string
    {
        return __('fc-onboarding::onboarding.analytics.title');
    }

    public function getTitle(): string
    {
        return __('fc-onboarding::onboarding.analytics.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.system');
    }

    public static function canAccess(): bool
    {
        return (bool) config('fc-onboarding.analytics.enabled', true)
            && Gate::allows((string) config('fc-onboarding.analytics.ability', 'access.onboarding_analytics'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    /** @return array<string, int|string> */
    public function summary(): array
    {
        $total = OnboardingProgress::query()->count();
        $finished = OnboardingProgress::query()->whereNotNull('wizard_completed_at')->count();
        $dismissed = OnboardingProgress::query()->where('checklist_dismissed', true)->count();

        return [
            'users' => $total,
            'finished' => $finished,
            'finished_rate' => $total === 0 ? 0 : (int) round($finished / $total * 100),
            'dismissed' => $dismissed,
            'dismissed_early' => OnboardingProgress::query()
                ->where('checklist_dismissed', true)
                ->whereNull('wizard_completed_at')
                ->count(),
        ];
    }

    /**
     * نسبة الاكتمال لكل خطوة — الأقل هي نقطة التوقّف.
     *
     * @return Collection<int, array{key: string, label: string, completed: int, rate: int}>
     */
    public function stepBreakdown(): Collection
    {
        $users = OnboardingProgress::query()->count();

        return Onboarding::steps()->values()->map(function (OnboardingStep $step) use ($users): array {
            $completed = OnboardingProgress::query()
                ->whereJsonContains('completed_steps', $step::key())
                ->count();

            return [
                'key' => $step::key(),
                'label' => $step->label(),
                'completed' => $completed,
                'rate' => $users === 0 ? 0 : (int) round($completed / $users * 100),
            ];
        })->values();
    }

    /** @return Collection<int, array{key: string, completed: int, rate: int}> */
    public function tourBreakdown(): Collection
    {
        $users = max(OnboardingProgress::query()->count(), 1);

        return collect((array) config('fc-onboarding.tours.registered', []))
            ->map(function (string $class) use ($users): array {
                /** @var Tour $tour */
                $tour = app($class);

                $completed = OnboardingProgress::query()
                    ->whereJsonContains('completed_tours', $tour::key())
                    ->count();

                return [
                    'key' => $tour::key(),
                    'completed' => $completed,
                    'rate' => (int) round($completed / $users * 100),
                ];
            })
            ->values();
    }
}
