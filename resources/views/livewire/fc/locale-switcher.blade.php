@php
    $next = collect($locales)->first(fn (string $locale): bool => $locale !== $current) ?? $current;
@endphp

<div>
    <x-filament::icon-button
        icon="heroicon-o-language"
        :label="__('common.locales.' . $next)"
        :tooltip="__('common.locales.' . $next)"
        wire:click="switch('{{ $next }}')"
        wire:loading.attr="disabled"
        color="gray"
        size="lg"
    />
</div>
