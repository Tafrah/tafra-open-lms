<x-filament::section compact icon="heroicon-o-arrow-up-circle" icon-color="primary" class="mb-6">
    <x-slot name="heading">{{ __('installer::installer.update.available.title', ['version' => $release['version']]) }}</x-slot>
    <x-slot name="description">{{ __('installer::installer.update.available.body', ['current' => $current]) }}</x-slot>
    <div class="flex flex-wrap gap-3">
        @if (filled($release['url']))
            <x-filament::button tag="a" :href="$release['url']" target="_blank" rel="noopener" size="sm">
                {{ __('installer::installer.update.available.download') }}
            </x-filament::button>
        @endif
        <x-filament::button tag="a" :href="config('installer.updates.guide')" target="_blank" rel="noopener" size="sm" color="gray">
            {{ __('installer::installer.update.available.how') }}
        </x-filament::button>
    </div>
</x-filament::section>
