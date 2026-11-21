@php
    $nextLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
@endphp
<form method="POST" action="{{ route('site.locale') }}">
    @csrf
    <input type="hidden" name="locale" value="{{ $nextLocale }}">
    <button type="submit" class="flex h-10 items-center gap-1.5 rounded-full px-3 text-sm font-bold text-ink transition hover:bg-primary/10 hover:text-primary"
            lang="{{ $nextLocale }}" aria-label="{{ __('common.locales.'.$nextLocale) }}">
        <x-storefront::icon name="language" />
        <span>{{ __('common.locales.'.$nextLocale) }}</span>
    </button>
</form>
