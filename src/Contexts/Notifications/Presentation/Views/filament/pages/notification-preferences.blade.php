<x-filament-panels::page>
    @foreach ($this->groupedCatalog() as $group => $items)
        <x-filament::section>
            <x-slot name="heading">
                {{ __('notifications::notifications.groups.' . $group) }}
            </x-slot>

            <div class="fi-section-content divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($items as $key => $definition)
                    <div class="flex flex-wrap items-center justify-between gap-4 py-3">
                        <div class="min-w-56">
                            <p class="text-sm font-medium">
                                {{ __('notifications::notifications.catalog.' . $key . '.label') }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ __('notifications::notifications.catalog.' . $key . '.description') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-4">
                            @foreach ($definition['channels'] as $channel)
                                @php($required = $this->isRequired($key, $channel))

                                <label
                                    class="flex items-center gap-2 text-sm"
                                    @if ($required) title="{{ __('notifications::notifications.preferences.required_hint') }}" @endif
                                >
                                    <input
                                        type="checkbox"
                                        class="fi-checkbox-input"
                                        wire:model="state.{{ $key }}.{{ $channel }}"
                                        @disabled($required)
                                    >
                                    <span>{{ __('notifications::notifications.channels.' . $channel) }}</span>
                                    @if ($required)
                                        <span class="fc-badge">{{ __('notifications::notifications.preferences.required') }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
