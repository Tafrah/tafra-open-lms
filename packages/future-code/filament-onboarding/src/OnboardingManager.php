<?php

declare(strict_types=1);

namespace FutureCode\Onboarding;

use Filament\Facades\Filament;
use FutureCode\Onboarding\Contracts\OnboardingStep;
use FutureCode\Onboarding\Models\OnboardingProgress;
use FutureCode\Onboarding\Tours\Tour;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final class OnboardingManager
{
    /** @var array<string, OnboardingProgress> */
    private array $progressCache = [];

    public function __construct(private readonly OnboardingRegistry $registry) {}

    public function enabled(): bool
    {
        return (bool) config('fc-onboarding.enabled', true);
    }

    /**
     * كل الخطوات المعرّفة في كل اللوحات — من غير فلترة صلاحيات.
     *
     * المصدر: الاكتشاف (`OnboardingRegistry`) + أي كلاس مضاف في الكونفيج،
     * ناقص `exclude`.
     *
     * @return Collection<int, OnboardingStep>
     */
    public function steps(): Collection
    {
        return $this->classes((array) config('fc-onboarding.steps', []), $this->registry->steps())
            ->map(static fn (string $class): OnboardingStep => app($class))
            ->sortBy(static fn (OnboardingStep $step): int => $step->sort())
            ->values();
    }

    /**
     * الخطوات اللي المستخدم ده مسموح له يشوفها **في اللوحة الحالية**.
     *
     * الخطوة اللي مالوش قدرتها بتختفي — مش بتظهر معطّلة، عشان
     * ما نوعدهوش بحاجة مش هيقدر يعملها.
     *
     * @return Collection<int, OnboardingStep>
     */
    public function stepsFor(?Authenticatable $user, ?string $panel = null): Collection
    {
        if ($user === null) {
            return collect();
        }

        $panel ??= $this->currentPanelId();

        return $this->steps()->filter(
            static fn (OnboardingStep $step): bool => $step->panel() === $panel
                && ($step->ability() === null || Gate::forUser($user)->allows($step->ability())),
        )->values();
    }

    /** @return Collection<int, OnboardingStep> */
    public function requiredStepsFor(?Authenticatable $user): Collection
    {
        return $this->stepsFor($user)->filter(
            static fn (OnboardingStep $step): bool => $step->isRequired(),
        )->values();
    }

    public function completionFor(?Authenticatable $user): float
    {
        $steps = $this->stepsFor($user);

        if ($steps->isEmpty()) {
            return 1.0;
        }

        $done = $steps->filter(static fn (OnboardingStep $step): bool => $step->isComplete())->count();

        return $done / $steps->count();
    }

    public function isCompleteFor(?Authenticatable $user): bool
    {
        return $this->stepsFor($user)->every(
            static fn (OnboardingStep $step): bool => $step->isComplete(),
        );
    }

    /** هل لسه فيه خطوة إلزامية ناقصة؟ */
    public function hasPendingRequiredSteps(?Authenticatable $user): bool
    {
        return $this->requiredStepsFor($user)->contains(
            static fn (OnboardingStep $step): bool => ! $step->isComplete(),
        );
    }

    /** سجل التقدّم للمستخدم الحالي — بيتعمل أول مرة تلقائياً. */
    public function progress(?Authenticatable $user = null, ?int $tenantId = null): OnboardingProgress
    {
        $user ??= auth()->user();
        $tenantId ??= $this->currentTenantId();

        $cacheKey = ($user?->getAuthIdentifier() ?? 'guest').':'.($tenantId ?? 'global');

        return $this->progressCache[$cacheKey] ??= OnboardingProgress::query()->firstOrCreate(
            ['user_id' => $user?->getAuthIdentifier(), 'tenant_id' => $tenantId],
            ['completed_steps' => [], 'completed_tours' => []],
        );
    }

    /**
     * كل الجولات المسجّلة (اكتشاف + كونفيج).
     *
     * @return Collection<int, Tour>
     */
    public function tours(): Collection
    {
        return $this->classes((array) config('fc-onboarding.tours.registered', []), $this->registry->tours())
            ->map(static fn (string $class): Tour => app($class))
            ->values();
    }

    /**
     * الجولات المسجّلة للصفحة دي في اللوحة دي.
     *
     * @return Collection<int, Tour>
     */
    public function toursFor(string $page, ?string $panel = null): Collection
    {
        $panel ??= $this->currentPanelId();

        return $this->tours()
            ->filter(static fn (Tour $tour): bool => in_array($page, $tour::pages(), true)
                && in_array($panel, $tour::panels(), true))
            ->values();
    }

    public function shouldAutoStart(Tour $tour, ?Authenticatable $user): bool
    {
        if (! config('fc-onboarding.tours.enabled', true) || ! config('fc-onboarding.tours.auto_start', true)) {
            return false;
        }

        return $user !== null && ! $this->progress($user)->hasCompletedTour($tour::key());
    }

    /**
     * @param array<int, mixed> $configured
     * @param list<class-string> $discovered
     * @return Collection<int, class-string>
     */
    private function classes(array $configured, array $discovered): Collection
    {
        $excluded = (array) config('fc-onboarding.exclude', []);

        return collect([...$discovered, ...$configured])
            ->filter(static fn (mixed $class): bool => is_string($class) && class_exists($class))
            ->unique()
            ->reject(static fn (string $class): bool => in_array($class, $excluded, true))
            ->values();
    }

    private function currentPanelId(): string
    {
        return Filament::getCurrentPanel()?->getId() ?? 'admin';
    }

    private function currentTenantId(): ?int
    {
        $tenant = Filament::getTenant();

        return $tenant?->getKey();
    }
}
