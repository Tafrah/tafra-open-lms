@php
    $appearance = app(\Src\Contexts\Settings\Domain\Settings\AppearanceSettings::class);
    $locale = app()->getLocale();
    $copy = $appearance->footer_text[$locale]
        ?? $appearance->footer_text[config('app.fallback_locale')]
        ?? '';
@endphp

<footer class="fc-footer">
    <div class="fc-footer__inner">
        <span class="fc-footer__copy">{{ $copy }}</span>

        <span class="fc-footer__by">
            {{ __('common.built_by') }}
            <span class="fc-footer__link">{{ __('common.company') }}</span>
        </span>

        <span class="fc-footer__version fc-code">v{{ config('app.version') }}</span>
    </div>
</footer>
