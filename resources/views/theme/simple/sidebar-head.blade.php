{{-- رأس السايدبار: لوجو واسم الأكاديمية + زرار التصغير (ss-head في طفرة) --}}
@php($brand = \App\Filament\Shell\AdminShell::brand())
<div class="ss-head">
    <a href="{{ filament()->getHomeUrl() }}" class="ss-brand" x-show="$store.sidebar.isOpen">
        @if ($brand['logo'])
            <img src="{{ $brand['logo'] }}" alt="" class="ss-brand-logo">
        @else
            <span class="ss-brand-letter" aria-hidden="true">{{ mb_substr($brand['name'], 0, 1) }}</span>
        @endif
        <span class="ss-brand-name">{{ $brand['name'] }}</span>
    </a>
    <button
        type="button"
        class="ss-icon-btn ss-collapse-btn"
        x-data="{}"
        x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        x-bind:aria-label="$store.sidebar.isOpen ? @js(__('shell.collapse')) : @js(__('shell.expand'))"
        x-bind:title="$store.sidebar.isOpen ? @js(__('shell.collapse')) : @js(__('shell.expand'))"
        aria-controls="fi-main-sidebar"
    >
        <x-phosphor-sidebar-bold class="ss-flip size-5" />
    </button>
</div>
