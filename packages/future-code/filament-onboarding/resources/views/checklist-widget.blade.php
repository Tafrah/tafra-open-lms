@php
    $steps = $this->getSteps();
    $percentage = $this->getCompletionPercentage();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->isComplete()
                ? __('fc-onboarding::onboarding.checklist.complete_heading')
                : __('fc-onboarding::onboarding.checklist.heading') }}
        </x-slot>

        <x-slot name="description">
            {{ $this->isComplete()
                ? __('fc-onboarding::onboarding.checklist.complete_description')
                : __('fc-onboarding::onboarding.checklist.description') }}
        </x-slot>

        @if ($this->isDismissible())
            <x-slot name="headerEnd">
                <x-filament::icon-button
                    icon="heroicon-o-x-mark"
                    :label="__('fc-onboarding::onboarding.checklist.dismiss')"
                    wire:click="dismiss"
                    color="gray"
                    size="sm"
                />
            </x-slot>
        @endif

        <div class="fi-section-content space-y-4">
            <div>
                <div class="mb-1 flex items-center justify-between text-sm">
                    <span>{{ __('fc-onboarding::onboarding.checklist.progress') }}</span>
                    <span class="fc-number">{{ $percentage }}%</span>
                </div>

                <div
                    class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10"
                    role="progressbar"
                    aria-valuenow="{{ $percentage }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div class="fc-onboarding-bar h-full" style="width: {{ $percentage }}%"></div>
                </div>
            </div>

            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($steps as $step)
                    <li class="flex flex-wrap items-center gap-3 py-3">
                        <x-filament::icon
                            :icon="$step->isComplete() ? 'heroicon-o-check-circle' : $step->icon()"
                            @class([
                                'h-6 w-6 shrink-0',
                                'text-green-600 dark:text-green-400' => $step->isComplete(),
                                'text-gray-400' => ! $step->isComplete(),
                            ])
                        />

                        <div class="min-w-48 flex-1">
                            <p @class(['text-sm font-medium', 'line-through opacity-60' => $step->isComplete()])>
                                {{ $step->label() }}
                            </p>
                            <p class="text-sm text-gray-500">{{ $step->description() }}</p>
                        </div>

                        @if (! $step->isComplete() && filled($step->url()))
                            <x-filament::button tag="a" :href="$step->url()" size="sm" color="gray">
                                {{ __('fc-onboarding::onboarding.checklist.start') }}
                            </x-filament::button>
                        @endif

                        @if ($step->isRequired() && ! $step->isComplete())
                            <span class="fc-badge">{{ __('fc-onboarding::onboarding.checklist.required') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
