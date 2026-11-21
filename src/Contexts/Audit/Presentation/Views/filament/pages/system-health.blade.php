<x-filament-panels::page>
    @php($results = $this->results())

    <x-filament::section>
        <x-slot name="heading">{{ __('audit::audit.health.heading') }}</x-slot>
        <x-slot name="description">
            {{ $this->lastRanAt() ? __('audit::audit.health.last_ran', ['time' => $this->lastRanAt()]) : __('audit::audit.health.never_ran') }}
        </x-slot>

        <div class="fi-section-content grid gap-3 md:grid-cols-2">
            @forelse ($results as $result)
                <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                    <div>
                        <p class="text-sm font-medium">{{ $result->label }}</p>
                        <p class="text-sm text-gray-500">{{ $result->notificationMessage ?: $result->shortSummary }}</p>
                    </div>

                    <x-filament::badge :color="match ($result->status) {
                        'ok' => 'success',
                        'warning' => 'warning',
                        'failed', 'crashed' => 'danger',
                        default => 'gray',
                    }">
                        {{ __('audit::audit.health.status.' . $result->status) }}
                    </x-filament::badge>
                </div>
            @empty
                <p class="text-sm text-gray-500">{{ __('audit::audit.health.never_ran') }}</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
