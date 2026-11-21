<x-filament-panels::page>
    @if ($this->canConnect())
        <x-filament::section
            icon="heroicon-o-link"
            :heading="__('ai::ai.connections.connect.heading')"
            :description="__('ai::ai.connections.connect.description')"
            id="ai-connect"
        >
            <div class="grid gap-3 text-sm">
                <div>
                    <span class="font-medium">{{ __('ai::ai.connections.connect.url') }}</span>
                    <code class="fc-code ms-2 select-all" dir="ltr">{{ $this->serverUrl() }}</code>
                </div>

                <ol class="list-decimal ps-5 space-y-1">
                    <li>{{ __('ai::ai.connections.connect.steps.claude') }}</li>
                    <li>{{ __('ai::ai.connections.connect.steps.chatgpt') }}</li>
                    <li>{{ __('ai::ai.connections.connect.steps.approve') }}</li>
                </ol>
            </div>
        </x-filament::section>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
