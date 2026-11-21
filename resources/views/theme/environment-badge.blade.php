@php
    $env = app()->environment();
@endphp

<div class="fc-env">
    <span>{{ __('common.environment') }}</span>
    <span class="fc-env__badge fc-env__badge--{{ $env }}">{{ $env }}</span>
</div>
