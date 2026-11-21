<x-filament-panels::page>
    {{ $this->form }}

    <x-filament::section>
        <x-slot name="heading">{{ $this->file ?? __('audit::audit.logs.no_file') }}</x-slot>
        <x-slot name="description">{{ __('audit::audit.logs.redacted_notice') }}</x-slot>

        <div class="fi-section-content overflow-x-auto">
            <pre class="fc-code max-h-[32rem] overflow-y-auto whitespace-pre-wrap break-all rounded-lg bg-gray-950 p-4 text-xs text-gray-100">@foreach ($this->lines() as $line){{ $line }}
@endforeach</pre>
        </div>
    </x-filament::section>
</x-filament-panels::page>
