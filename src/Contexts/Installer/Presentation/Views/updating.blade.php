@php($locale = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @if ($state === 'busy')
        <meta http-equiv="refresh" content="15">
    @endif
    <title>{{ __("installer::installer.update.{$state}.title") }}</title>
    <link rel="stylesheet" href="{{ request()->getBaseUrl() }}/installer/installer.css">
</head>
<body style="--primary: {{ config('installer.tenant.primary_color') }}">
<main class="shell">
    <section class="card">
        <h1>{{ __("installer::installer.update.{$state}.title") }}</h1>
        <p class="muted">{{ __("installer::installer.update.{$state}.body") }}</p>
    </section>
</main>
</body>
</html>
