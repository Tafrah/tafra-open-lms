<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Facades;

use FutureCode\Onboarding\OnboardingManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool enabled()
 * @method static \Illuminate\Support\Collection<int, \FutureCode\Onboarding\Contracts\OnboardingStep> steps()
 * @method static \Illuminate\Support\Collection<int, \FutureCode\Onboarding\Contracts\OnboardingStep> stepsFor(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static \Illuminate\Support\Collection<int, \FutureCode\Onboarding\Contracts\OnboardingStep> requiredStepsFor(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static float completionFor(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool isCompleteFor(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool hasPendingRequiredSteps(?\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static \FutureCode\Onboarding\Models\OnboardingProgress progress(?\Illuminate\Contracts\Auth\Authenticatable $user = null, ?int $tenantId = null)
 * @method static \Illuminate\Support\Collection<int, \FutureCode\Onboarding\Tours\Tour> toursFor(string $page)
 * @method static bool shouldAutoStart(\FutureCode\Onboarding\Tours\Tour $tour, ?\Illuminate\Contracts\Auth\Authenticatable $user)
 *
 * @see OnboardingManager
 */
final class Onboarding extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OnboardingManager::class;
    }
}
