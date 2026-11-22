@php use Src\Contexts\Transfer\Presentation\Filament\Pages\TafraFeatures; use Src\Contexts\Transfer\Presentation\Filament\Pages\DataTransfer; @endphp
<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach (TafraFeatures::FEATURES as $key => $icon)
            <x-filament::section :icon="$icon" icon-color="primary" data-tafra-feature="{{ $key }}">
                <x-slot name="heading">{{ __("transfer::transfer.tafra.features.{$key}.title") }}</x-slot>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __("transfer::transfer.tafra.features.{$key}.body") }}</p>
                <div class="mt-4">
                    <x-filament::button tag="a" :href="TafraFeatures::contactUrl($key)" target="_blank" rel="noopener" size="sm" color="gray" icon="heroicon-o-chat-bubble-oval-left">
                        {{ __('transfer::transfer.tafra.contact') }}
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section icon="heroicon-o-arrows-right-left">
        <x-slot name="heading">{{ __('transfer::transfer.tafra.move.title') }}</x-slot>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('transfer::transfer.tafra.move.body') }}</p>
        <div class="mt-4">
            <x-filament::button tag="a" :href="DataTransfer::getUrl()" size="sm">{{ __('transfer::transfer.tafra.move.action') }}</x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
