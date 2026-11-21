@php
    $locale = app()->getLocale();
    $other = $locale === 'ar' ? 'en' : 'ar';
    $base = request()->getBaseUrl();
    $steps = ['requirements', 'database', 'academy', 'finished'];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('installer::installer.title') }} — @yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap">
    <link rel="stylesheet" href="{{ $base }}/installer/installer.css">
</head>
<body style="--primary: {{ config('installer.tenant.primary_color') }}">
<main class="shell">
    <header class="topbar">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">T</span>
            <div>
                <strong>{{ __('installer::installer.brand') }}</strong>
                <span class="muted">{{ __('installer::installer.title') }}</span>
            </div>
        </div>
        @if (($step ?? 0) < 4)
            <a class="lang" href="{{ route('installer.locale', ['locale' => $other], false) }}">{{ __('installer::installer.switch_language') }}</a>
        @endif
    </header>

    <ol class="steps" aria-label="{{ __('installer::installer.title') }}">
        @foreach ($steps as $index => $name)
            <li @class(['done' => ($step ?? 1) > $index + 1, 'current' => ($step ?? 1) === $index + 1])>
                <span class="bullet">{{ $index + 1 }}</span>
                <span>{{ __('installer::installer.nav.'.$name) }}</span>
            </li>
        @endforeach
    </ol>

    <section class="card">
        <h1>@yield('title')</h1>
        <p class="lead">@yield('lead')</p>

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
                @if (session('installer.details'))
                    <details>
                        <summary>{{ __('installer::installer.technical_details') }}</summary>
                        <code dir="ltr">{{ session('installer.details') }}</code>
                    </details>
                @endif
            </div>
        @endif

        @yield('content')
    </section>

    <footer class="footer muted">{{ __('installer::installer.footer') }}</footer>
</main>
</body>
</html>
