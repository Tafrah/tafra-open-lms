<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Widgets;

use Filament\Widgets\Widget;
use FutureCode\Onboarding\Contracts\OnboardingStep;
use FutureCode\Onboarding\Facades\Onboarding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * قائمة المهام — بتفضل في لوحة المعلومات لحد ما كل الخطوات تكتمل.
 *
 * بتقرا الحالة الفعلية من النظام في كل تحميل، مش من علامة محفوظة
 * (docs/15 بند ٤).
 */
final class OnboardingChecklistWidget extends Widget
{
    protected string $view = 'fc-onboarding::checklist-widget';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        if (! Onboarding::enabled() || ! config('fc-onboarding.checklist.enabled', true)) {
            return false;
        }

        if (! Gate::allows('widget.onboarding_checklist')) {
            return false;
        }

        $progress = Onboarding::progress();

        if ($progress->isDismissed()) {
            return false;
        }

        return ! (config('fc-onboarding.checklist.hide_when_complete', true)
            && Onboarding::isCompleteFor(auth()->user()));
    }

    /** @return Collection<int, OnboardingStep> */
    public function getSteps(): Collection
    {
        // المكتملة تحت والباقية فوق — المستخدم بيدوّر على اللي فاضل
        return Onboarding::stepsFor(auth()->user())
            ->sortBy(static fn (OnboardingStep $step): int => $step->isComplete() ? 1 : 0)
            ->values();
    }

    public function getCompletionPercentage(): int
    {
        return (int) round(Onboarding::completionFor(auth()->user()) * 100);
    }

    public function isComplete(): bool
    {
        return Onboarding::isCompleteFor(auth()->user());
    }

    public function isDismissible(): bool
    {
        return (bool) config('fc-onboarding.checklist.dismissible', true);
    }

    public function dismiss(): void
    {
        Onboarding::progress()->dismiss();

        $this->dispatch('$refresh');
    }
}
