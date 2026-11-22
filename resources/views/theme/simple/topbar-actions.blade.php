{{-- اسم الأكاديمية + «الذهاب لبوابة الطلاب» (Navbar في طفرة) --}}
@php($brand = \App\Filament\Shell\AdminShell::brand())
<div class="ss-navbar">
    <div class="ss-tenant-chip">
        @if ($brand['logo'])
            <img src="{{ $brand['logo'] }}" alt="">
        @endif
        <span>{{ $brand['name'] }}</span>
    </div>
    <a href="{{ \App\Filament\Shell\AdminShell::portalUrl() }}" target="_blank" rel="noopener" class="ss-portal-btn" title="{{ __('shell.topbar.portal') }}">
        <x-phosphor-arrow-square-out-bold class="ss-flip size-[18px]" />
        <span>{{ __('shell.topbar.portal') }}</span>
    </a>
</div>
