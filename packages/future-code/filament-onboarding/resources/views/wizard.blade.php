@php
    $steps = $this->steps();
    $step = $this->currentStep();
    $percentage = $this->completionPercentage();
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ $this->progressLabel() }}</x-slot>

        <div class="fi-section-content space-y-6">
            <div
                class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                role="progressbar"
                aria-valuenow="{{ $percentage }}"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div class="fc-onboarding-bar h-full" style="width: {{ $percentage }}%"></div>
            </div>

            @if ($step)
                <div class="flex flex-wrap items-start gap-4">
                    <x-filament::icon :icon="$step->icon()" class="h-8 w-8 shrink-0 text-gray-400" />

                    <div class="min-w-64 flex-1 space-y-1">
                        <p class="text-lg font-medium">{{ $step->label() }}</p>
                        <p class="text-sm text-gray-500">{{ $step->description() }}</p>

                        @if ($step->isComplete())
                            <p class="text-sm text-green-600 dark:text-green-400">
                                {{ __('fc-onboarding::onboarding.wizard.step_done') }}
                            </p>
                        @elseif ($step->isRequired())
                            <p class="text-sm text-amber-600 dark:text-amber-400">
                                {{ __('fc-onboarding::onboarding.wizard.step_required') }}
                            </p>
                        @endif
                    </div>

                    @if (filled($step->url()))
                        <x-filament::button tag="a" :href="$step->url()">
                            {{ __('fc-onboarding::onboarding.wizard.open') }}
                        </x-filament::button>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-filament::button color="gray" wire:click="previous" :disabled="$this->currentIndex === 0">
                        {{ __('common.previous') }}
                    </x-filament::button>

                    <x-filament::button wire:click="next" :disabled="$step->isRequired() && ! $step->isComplete()">
                        {{ __('common.next') }}
                    </x-filament::button>
                </div>
            @else
                <div class="space-y-3 text-center">
                    <x-filament::icon icon="heroicon-o-sparkles" class="mx-auto h-10 w-10 text-green-500" />
                    <p class="text-lg font-medium">{{ __('fc-onboarding::onboarding.wizard.congrats_heading') }}</p>
                    <p class="text-sm text-gray-500">{{ __('fc-onboarding::onboarding.wizard.congrats_body') }}</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
