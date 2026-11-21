<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2">
        <x-filament::section icon="heroicon-o-arrow-down-tray">
            <x-slot name="heading">{{ __('transfer::transfer.page.export_card') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('transfer::transfer.page.export_body') }}</p>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-arrow-up-tray">
            <x-slot name="heading">{{ __('transfer::transfer.page.import_card') }}</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('transfer::transfer.page.import_body') }}</p>
        </x-filament::section>
    </div>

    @if ($report)
        <x-filament::section :heading="$report['dry_run'] ? __('transfer::transfer.page.dry_run_report') : __('transfer::transfer.page.import_report')">
            @if ($report['dry_run'])
                <p class="mb-4 text-sm font-medium text-warning-600">{{ __('transfer::transfer.page.dry_run_note') }}</p>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-start dark:border-white/10">
                            <th class="py-2 text-start">{{ __('transfer::transfer.console.table') }}</th>
                            <th class="py-2 text-start">{{ __('transfer::transfer.console.read') }}</th>
                            <th class="py-2 text-start">{{ __('transfer::transfer.console.imported') }}</th>
                            <th class="py-2 text-start">{{ __('transfer::transfer.console.merged') }}</th>
                            <th class="py-2 text-start">{{ __('transfer::transfer.console.skipped') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['tables'] as $table => $counts)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-1.5 font-mono text-xs">{{ $table }}</td>
                                <td class="py-1.5">{{ fc_number($counts['read']) }}</td>
                                <td class="py-1.5 text-success-600">{{ fc_number($counts['imported']) }}</td>
                                <td class="py-1.5">{{ fc_number($counts['merged']) }}</td>
                                <td class="py-1.5 text-danger-600">{{ fc_number($counts['skipped']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (! empty($report['warnings']))
                <ul class="mt-4 list-disc space-y-1 ps-5 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($report['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
