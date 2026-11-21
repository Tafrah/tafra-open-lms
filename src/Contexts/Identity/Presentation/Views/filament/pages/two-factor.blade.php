<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->isEnabled()
                ? __('identity::identity.two_factor.enabled')
                : __('identity::identity.two_factor.disabled') }}
        </x-slot>

        @if ($this->isPending())
            <div class="fi-section-content space-y-4">
                <p class="text-sm">{{ __('identity::identity.two_factor.scan_heading') }}</p>
                <p class="text-sm text-gray-500">{{ __('identity::identity.two_factor.scan_body') }}</p>

                <div class="inline-block rounded-lg bg-white p-4">
                    {!! $this->qrCodeSvg() !!}
                </div>

                <p class="text-sm">
                    {{ __('identity::identity.two_factor.setup_key') }}:
                    <span class="fc-code">{{ $this->setupKey() }}</span>
                </p>
            </div>
        @endif

        @if (filled($recoveryCodes))
            <div class="fi-section-content mt-6 space-y-2">
                <p class="text-sm font-medium">{{ __('identity::identity.two_factor.recovery_heading') }}</p>
                <p class="text-sm text-gray-500">{{ __('identity::identity.two_factor.recovery_body') }}</p>

                <ul class="mt-2 grid grid-cols-2 gap-2">
                    @foreach ($recoveryCodes as $code)
                        <li class="fc-code rounded bg-gray-50 px-3 py-1 text-sm dark:bg-gray-900">{{ $code }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
