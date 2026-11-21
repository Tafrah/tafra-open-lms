@php
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
@endphp

<div
    wire:ignore
    x-data="{
        steps: @js($steps),
        tourKey: @js($tourKey),
        rtl: @js($isRtl),
        start() {
            if (! this.steps.length || typeof window.fcStartTour !== 'function') {
                return;
            }

            window.fcStartTour(this.steps, {
                rtl: this.rtl,
                onDone: () => $wire.markCompleted(this.tourKey),
            });
        },
    }"
    x-init="
        @if ($autoStart) $nextTick(() => start()); @endif
        $wire.on('fc-tour-start', () => start());
        window.addEventListener('fc-tour-replay', () => start());
    "
>
    @if ($tourKey && config('fc-onboarding.tours.show_replay'))
        <button
            type="button"
            class="fi-icon-btn"
            x-on:click="start()"
            :title="@js(__('fc-onboarding::onboarding.tours.replay'))"
            aria-label="{{ __('fc-onboarding::onboarding.tours.replay') }}"
        >
            <x-filament::icon icon="heroicon-o-question-mark-circle" class="h-5 w-5" />
        </button>
    @endif
</div>
