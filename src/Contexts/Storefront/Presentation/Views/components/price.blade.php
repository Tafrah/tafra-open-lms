@props(['course', 'size' => 'sm'])
@php
    $free = $course->isFree();
    $discounted = ! $free && $course->hasActiveDiscount();
    $currency = $course->price_currency ?: 'EGP';
@endphp
@if ($free)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full bg-success/15 px-3 py-1 font-extrabold text-success-ink '.($size === 'lg' ? 'text-lg' : 'text-sm')]) }}>{{ __('storefront::storefront.course.free') }}</span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
        <span class="inline-flex items-center rounded-full bg-linear-to-l from-primary to-secondary p-1">
            <span class="rounded-full bg-white px-3 py-0.5 font-extrabold text-navy {{ $size === 'lg' ? 'text-xl' : 'text-sm' }} dark:bg-surface">{{ fc_currency($course->currentPrice(), $currency) }}</span>
        </span>
        @if ($discounted)
            <del class="text-xs font-bold text-muted" aria-label="{{ __('storefront::storefront.course.old_price') }}">{{ fc_currency((float) $course->price, $currency) }}</del>
        @endif
    </span>
@endif
