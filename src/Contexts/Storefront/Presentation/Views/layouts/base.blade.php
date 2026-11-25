@php
    $locale = app()->getLocale();
    $dir = $locale === 'ar' ? 'rtl' : 'ltr';
    $pageTitle = trim($__env->yieldContent('title'));
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle !== '' ? $pageTitle.' — '.$site->platformName : $site->platformName }}</title>
    <meta name="description" content="{{ trim($__env->yieldContent('description')) ?: ($site->heroDescription ?? __('storefront::storefront.meta.description', ['name' => $site->platformName])) }}">
    <meta property="og:title" content="{{ $pageTitle !== '' ? $pageTitle : $site->platformName }}">
    <meta property="og:site_name" content="{{ $site->platformName }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @elseif ($site->teacherPhotoUrl)
        <meta property="og:image" content="{{ $site->teacherPhotoUrl }}">
    @endif
    <meta name="robots" content="@yield('robots', 'index,follow')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ $site->fontQuery() }}&display=swap">

    {{-- الوضع الليلي قبل أول رسم — من غير وميض أبيض --}}
    <script>
        (function () {
            var mode = 'system';
            try { mode = localStorage.getItem('storefront-theme') || 'system'; } catch (e) {}
            var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.dataset.theme = mode;
        })();
    </script>

    @vite(['resources/css/site.css', 'resources/js/site.js'])

    {{-- ألوان العلامة والخط من «إعدادات الموقع» (متأكَّد منها في SiteSettingsData) --}}
    <style>
        :root {
            --color-primary: {{ $site->primaryColor }};
            --color-secondary: {{ $site->secondaryColor }};
            --font-primary: '{{ $site->fontFamily }}';
        }
    </style>

    @livewireStyles
    @stack('head')
</head>
<body class="@yield('body_class', 'bg-surface') min-h-screen">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-[100] focus:rounded-card focus:bg-primary focus:px-4 focus:py-2 focus:text-white">
        {{ __('storefront::storefront.a11y.skip') }}
    </a>

    {{-- نسخة التجربة (DemoServiceProvider) --}}
    @isset($demo)
        @include('demo::site-banner', $demo)
    @endisset

    @yield('body')

    @include('storefront::partials.toasts')

    @if ($site->floatingWhatsapp && $site->whatsappUrl())
        <a href="{{ $site->whatsappUrl() }}" target="_blank" rel="noopener noreferrer"
           class="fixed bottom-20 end-4 z-40 grid size-14 place-items-center rounded-full bg-success text-white shadow-lg transition hover:scale-110 md:bottom-6"
           aria-label="{{ __('storefront::storefront.whatsapp.floating') }}">
            <x-storefront::icon name="whatsapp" class="size-7" />
        </a>
    @endif

    @livewireScripts
    @stack('scripts')
</body>
</html>
