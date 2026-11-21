<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use FutureCode\Onboarding\Contracts\OnboardingStep;
use FutureCode\Onboarding\Facades\Onboarding;
use Illuminate\Support\Collection;

/**
 * معالج الإعداد — خطوة واحدة في الشاشة، وشريط تقدّم واضح.
 *
 * كل خطوة بتتحقق من اكتمالها **فعلياً** — مش بمجرد ضغط «التالي»
 * (docs/15 بند ٣).
 */
final class SetupWizard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'fc-onboarding::wizard';

    public int $currentIndex = 0;

    public static function getSlug(?Panel $panel = null): string
    {
        return (string) config('fc-onboarding.wizard.slug', 'setup');
    }

    public static function canAccess(): bool
    {
        return Onboarding::enabled() && (bool) config('fc-onboarding.wizard.enabled', true);
    }

    public function getTitle(): string
    {
        return __('fc-onboarding::onboarding.wizard.title');
    }

    public function getHeading(): string
    {
        return __('fc-onboarding::onboarding.wizard.heading');
    }

    public function getSubheading(): string
    {
        return __('fc-onboarding::onboarding.wizard.subheading');
    }

    /** @return Collection<int, OnboardingStep> */
    public function steps(): Collection
    {
        return Onboarding::stepsFor(auth()->user());
    }

    public function currentStep(): ?OnboardingStep
    {
        return $this->steps()->get($this->currentIndex);
    }

    public function progressLabel(): string
    {
        return __('fc-onboarding::onboarding.wizard.progress', [
            'current' => fc_number(min($this->currentIndex + 1, max($this->steps()->count(), 1))),
            'total' => fc_number($this->steps()->count()),
        ]);
    }

    public function completionPercentage(): int
    {
        return (int) round(Onboarding::completionFor(auth()->user()) * 100);
    }

    public function isFinished(): bool
    {
        return $this->currentIndex >= $this->steps()->count();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('skip')
                ->label(__('fc-onboarding::onboarding.wizard.skip'))
                ->color('gray')
                ->visible(fn (): bool => $this->currentStep() !== null
                    && ! $this->currentStep()->isRequired())
                ->action('next'),

            Action::make('finish')
                ->label(__('fc-onboarding::onboarding.wizard.finish'))
                ->icon('heroicon-o-check')
                ->visible(fn (): bool => ! Onboarding::hasPendingRequiredSteps(auth()->user()))
                ->action('finish'),
        ];
    }

    public function next(): void
    {
        $this->currentIndex = min($this->currentIndex + 1, $this->steps()->count());
    }

    public function previous(): void
    {
        $this->currentIndex = max($this->currentIndex - 1, 0);
    }

    public function finish(): void
    {
        Onboarding::progress()->markWizardCompleted();

        $this->redirect(filament()->getUrl());
    }
}
