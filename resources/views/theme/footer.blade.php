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
            <a class="fc-footer__link" href="{{ config('storefront.tafra_url') }}" target="_blank" rel="noopener">{{ __('common.company') }}</a>
        </span>

        <span class="fc-footer__version fc-code">v{{ config('app.version') }}</span>
    </div>
</footer>
