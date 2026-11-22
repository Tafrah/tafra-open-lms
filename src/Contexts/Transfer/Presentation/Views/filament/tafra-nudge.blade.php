<x-filament::section compact icon="heroicon-o-sparkles" icon-color="primary" class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ __("transfer::transfer.tafra.nudge.{$nudge['kind']}", ['count' => fc_number($nudge['count'])]) }}</p>
        <x-filament::button tag="a" :href="$url" size="sm" color="gray">{{ __('transfer::transfer.tafra.nudge.action') }}</x-filament::button>
    </div>
</x-filament::section>
