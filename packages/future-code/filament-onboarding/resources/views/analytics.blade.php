@php
    $summary = $this->summary();
    $steps = $this->stepBreakdown();
    $lowest = $steps->sortBy('rate')->first();
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('fc-onboarding::onboarding.analytics.summary') }}</x-slot>

        <div class="fi-section-content grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'users' => __('fc-onboarding::onboarding.analytics.users'),
                'finished_rate' => __('fc-onboarding::onboarding.analytics.finished_rate'),
                'dismissed' => __('fc-onboarding::onboarding.analytics.dismissed'),
                'dismissed_early' => __('fc-onboarding::onboarding.analytics.dismissed_early'),
            ] as $key => $label)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                    <p class="fc-number text-2xl font-semibold">
                        {{ fc_number($summary[$key]) }}{{ str_contains($key, 'rate') ? '%' : '' }}
                    </p>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">{{ __('fc-onboarding::onboarding.analytics.steps') }}</x-slot>
        <x-slot name="description">{{ __('fc-onboarding::onboarding.analytics.steps_hint') }}</x-slot>

        <div class="fi-section-content space-y-3">
            @foreach ($steps as $step)
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-sm">
                        <span @class(['font-medium', 'text-amber-600 dark:text-amber-400' => $lowest && $step['key'] === $lowest['key']])>
                            {{ $step['label'] }}
                            @if ($lowest && $step['key'] === $lowest['key'])
                                — {{ __('fc-onboarding::onboarding.analytics.drop_off') }}
                            @endif
                        </span>
                        <span class="fc-number">{{ $step['rate'] }}%</span>
                    </div>

                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="fc-onboarding-bar h-full" style="width: {{ $step['rate'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
