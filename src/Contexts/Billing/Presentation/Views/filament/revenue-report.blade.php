@php
    $report = $this->report;
    $totals = $report->totals();
    $months = $report->byMonth();
    $courses = $report->byCourse();
    $methods = $report->byMethod();
    $currency = __('academy::academy.currency');
    $money = fn (float $value): string => fc_number($value, 2).' '.$currency;
@endphp

<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-calendar-days" class="fc-revenue-range">
        <x-slot name="heading">{{ __('billing::billing.report.range') }}</x-slot>
        <div class="flex flex-wrap items-end gap-4">
            <label class="grid gap-1 text-sm">
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('billing::billing.report.from') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live.debounce.500ms="from" />
                </x-filament::input.wrapper>
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('billing::billing.report.to') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live.debounce.500ms="to" />
                </x-filament::input.wrapper>
            </label>
            <x-filament::button color="gray" wire:click="resetRange" icon="heroicon-o-arrow-path">
                {{ __('billing::billing.report.last_12_months') }}
            </x-filament::button>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ __('billing::billing.report.showing', ['from' => $report->from()->format('d M Y'), 'to' => $report->to()->format('d M Y')]) }}
        </p>
    </x-filament::section>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 fc-revenue-stats">
        @foreach ([
            'collected' => ['heroicon-o-banknotes', 'text-success-600 dark:text-success-400'],
            'transfers' => ['heroicon-o-arrows-right-left', 'text-primary-600 dark:text-primary-400'],
            'card_redemptions' => ['heroicon-o-ticket', 'text-warning-600 dark:text-warning-400'],
            'wallet_purchases' => ['heroicon-o-wallet', 'text-info-600 dark:text-info-400'],
        ] as $key => [$icon, $color])
            <x-filament::section>
                <div class="flex items-start gap-3">
                    <x-filament::icon :icon="$icon" @class(['size-6 shrink-0', $color]) />
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('billing::billing.report.lines.'.$key) }}</p>
                        <p class="text-xl font-bold text-gray-950 dark:text-white">{{ $money($totals[$key]) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('billing::billing.report.hints.'.$key) }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section icon="heroicon-o-calendar" class="fc-revenue-months">
        <x-slot name="heading">{{ __('billing::billing.report.by_month') }}</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="py-2 text-start">{{ __('billing::billing.report.columns.month') }}</th>
                        <th class="py-2 text-start">{{ __('billing::billing.report.lines.transfers') }}</th>
                        <th class="py-2 text-start">{{ __('billing::billing.report.lines.card_redemptions') }}</th>
                        <th class="py-2 text-start">{{ __('billing::billing.report.lines.collected') }}</th>
                        <th class="py-2 text-start">{{ __('billing::billing.report.lines.wallet_purchases') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($months as $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-1.5 font-medium">{{ \Carbon\CarbonImmutable::createFromFormat('Y-m', $row['month'])->startOfMonth()->translatedFormat('F Y') }}</td>
                            <td class="py-1.5">{{ $money($row['transfers']) }}</td>
                            <td class="py-1.5">{{ $money($row['card_redemptions']) }}</td>
                            <td class="py-1.5 font-bold text-success-700 dark:text-success-400">{{ $money($row['collected']) }}</td>
                            <td class="py-1.5 text-gray-500 dark:text-gray-400">{{ $money($row['wallet_purchases']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-filament::section icon="heroicon-o-academic-cap">
            <x-slot name="heading">{{ __('billing::billing.report.by_course') }}</x-slot>
            @if ($courses === [])
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('billing::billing.report.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-white/10">
                                <th class="py-2 text-start">{{ __('billing::billing.report.columns.course') }}</th>
                                <th class="py-2 text-start">{{ __('billing::billing.report.columns.count') }}</th>
                                <th class="py-2 text-start">{{ __('billing::billing.report.lines.transfers') }}</th>
                                <th class="py-2 text-start">{{ __('billing::billing.report.lines.wallet_purchases') }}</th>
                                <th class="py-2 text-start">{{ __('billing::billing.report.columns.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $row)
                                <tr class="border-b border-gray-100 dark:border-white/5">
                                    <td class="py-1.5 font-medium">{{ $row['name'] }}</td>
                                    <td class="py-1.5">{{ fc_number($row['count']) }}</td>
                                    <td class="py-1.5">{{ $money($row['transfers']) }}</td>
                                    <td class="py-1.5">{{ $money($row['wallet_purchases']) }}</td>
                                    <td class="py-1.5 font-bold">{{ $money($row['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        <x-filament::section icon="heroicon-o-credit-card">
            <x-slot name="heading">{{ __('billing::billing.report.by_method') }}</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 text-start">{{ __('billing::billing.report.columns.method') }}</th>
                            <th class="py-2 text-start">{{ __('billing::billing.report.columns.count') }}</th>
                            <th class="py-2 text-start">{{ __('billing::billing.report.columns.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($methods as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-1.5">
                                    <span class="font-medium">{{ $row['name'] }}</span>
                                    @if ($row['detail'])
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $row['detail'] }}</span>
                                    @endif
                                </td>
                                <td class="py-1.5">{{ fc_number($row['count']) }}</td>
                                <td class="py-1.5 font-bold">{{ $money($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
