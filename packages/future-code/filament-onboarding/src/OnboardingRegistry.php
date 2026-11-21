<?php

declare(strict_types=1);

namespace FutureCode\Onboarding;

use FutureCode\Onboarding\Contracts\OnboardingStep;
use FutureCode\Onboarding\Tours\Tour;

/**
 * سجل الخطوات والجولات — بيتملى بالاكتشاف مش بقايمة مكتوبة بالإيد.
 *
 * القوايم اليدوية في الكونفيج كانت بتتنسى (`docs/21` بند ٢٤): ميزة جديدة
 * بجولة جديدة، والجولة مابتشتغلش لأن محدش ضافها للمصفوفة. التطبيق بيسجّل
 * هنا كل `*Step` و`*Tour` بيلاقيهم في السياقات (`docs/15` بند ١٠).
 */
final class OnboardingRegistry
{
    /** @var array<class-string<OnboardingStep>, true> */
    private array $steps = [];

    /** @var array<class-string<Tour>, true> */
    private array $tours = [];

    /** @param class-string<OnboardingStep> $class */
    public function step(string $class): void
    {
        $this->steps[$class] = true;
    }

    /** @param class-string<Tour> $class */
    public function tour(string $class): void
    {
        $this->tours[$class] = true;
    }

    /** @return list<class-string<OnboardingStep>> */
    public function steps(): array
    {
        return array_keys($this->steps);
    }

    /** @return list<class-string<Tour>> */
    public function tours(): array
    {
        return array_keys($this->tours);
    }
}
