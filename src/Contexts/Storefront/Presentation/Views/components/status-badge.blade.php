@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $tone = match ($value) {
        'paid', 'approved', 'graded', 'passed', 'deposit' => 'bg-success/15 text-success-ink',
        'rejected', 'failed', 'withdraw' => 'bg-danger/15 text-danger-ink',
        'pending' => 'bg-warning/15 text-warning-ink',
        default => 'bg-surface-alt text-body',
    };
    $label = $status instanceof \Filament\Support\Contracts\HasLabel ? $status->getLabel() : $slot;
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold '.$tone]) }}>{{ $label }}</span>
