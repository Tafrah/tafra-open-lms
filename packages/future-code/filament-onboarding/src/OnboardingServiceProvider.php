<?php

declare(strict_types=1);

namespace FutureCode\Onboarding;

use FutureCode\Onboarding\Livewire\TourRunner;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class OnboardingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fc-onboarding.php', 'fc-onboarding');

        $this->app->singleton(OnboardingRegistry::class);
        $this->app->singleton(OnboardingManager::class);

        // نفس سبب ContextServiceProvider: التسميات بتتقيّم وقت بناء اللوحة،
        // واللي بيحصل قبل boot() بتاع المزوّد ده.
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'fc-onboarding');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'fc-onboarding');

        Livewire::component('fc-onboarding.tour-runner', TourRunner::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/fc-onboarding.php' => config_path('fc-onboarding.php'),
            ], 'fc-onboarding-config');
        }
    }
}
