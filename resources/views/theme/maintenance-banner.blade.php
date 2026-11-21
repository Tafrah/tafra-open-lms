@php
    try {
        $general = app(\Src\Contexts\Settings\Domain\Settings\GeneralSettings::class);
        $isMaintenance = $general->maintenance_mode;
        $message = $general->maintenance_message[app()->getLocale()]
            ?? $general->maintenance_message[config('app.fallback_locale')]
            ?? '';
    } catch (\Throwable) {
        $isMaintenance = false;
        $message = '';
    }
@endphp

@if ($isMaintenance)
    <div class="fc-maintenance" role="status">
        <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-4 w-4" />
        <span>{{ $message }}</span>
    </div>
@endif
