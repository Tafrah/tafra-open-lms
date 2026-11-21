<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('storefront::storefront.no_academy.title') }}</title>
    @vite(['resources/css/site.css'])
</head>
<body class="grid min-h-screen place-items-center bg-canvas p-6">
    <main class="max-w-md space-y-4 text-center">
        <h1 class="text-2xl font-extrabold text-navy">{{ __('storefront::storefront.no_academy.title') }}</h1>
        <p class="leading-8 text-body">{{ __('storefront::storefront.no_academy.body') }}</p>
        <p><a href="{{ config('storefront.tafra_url') }}" class="font-bold text-tafra" rel="noopener">{{ __('storefront::storefront.credit.developed_by') }} {{ __('storefront::storefront.credit.tafra') }}</a></p>
    </main>
</body>
</html>
