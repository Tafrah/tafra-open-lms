<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('audit::audit.backups.heading') }}</x-slot>
        <x-slot name="description">{{ __('audit::audit.backups.description') }}</x-slot>

        <div class="fi-section-content overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-start text-gray-500">
                        <th class="py-2 text-start">{{ __('audit::audit.backups.fields.file') }}</th>
                        <th class="py-2 text-start">{{ __('audit::audit.backups.fields.disk') }}</th>
                        <th class="py-2 text-start">{{ __('audit::audit.backups.fields.size') }}</th>
                        <th class="py-2 text-start">{{ __('audit::audit.backups.fields.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->backups() as $backup)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="fc-code py-2">{{ basename($backup['path']) }}</td>
                            <td class="fc-code py-2">{{ $backup['disk'] }}</td>
                            <td class="fc-number py-2">{{ fc_number($backup['size'] / 1048576, 1) }} MB</td>
                            <td class="py-2">{{ \Carbon\CarbonImmutable::createFromTimestamp($backup['modified'])->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-gray-500">{{ __('audit::audit.backups.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
