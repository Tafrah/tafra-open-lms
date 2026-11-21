@extends('storefront::layouts.base')

@section('robots', 'noindex,nofollow')
@section('body_class', 'bg-canvas')

@section('body')
    @php
        $me = auth('student')->user();
        $menu = [
            ['site.dashboard', 'grid', 'nav.dashboard', ['site.dashboard']],
            ['site.my-courses', 'book', 'nav.my_courses', ['site.my-courses']],
            ['site.exams', 'exam', 'nav.exams', ['site.exams', 'site.attempts.*']],
            ['site.invoices', 'document', 'nav.invoices', ['site.invoices', 'site.checkout.*']],
            ['site.wallet', 'wallet', 'nav.wallet', ['site.wallet']],
            ['site.profile', 'user', 'nav.profile', ['site.profile']],
        ];
        $bottom = [$menu[0], $menu[1], $menu[4], $menu[5]];
    @endphp

    {{-- السايدبار العائم (ديسكتوب) --}}
    <aside class="fixed inset-y-4 start-4 z-40 hidden w-72 flex-col rounded-float border border-line bg-surface p-4 shadow-sm lg:flex" aria-label="{{ __('storefront::storefront.a11y.student_nav') }}">
        <div class="px-2 pb-4">@include('storefront::partials.logo')</div>
        <div class="mb-4 flex items-center gap-3 rounded-item bg-surface-alt p-3">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary text-lg font-bold text-white" aria-hidden="true">{{ mb_substr($me->name, 0, 1) }}</span>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-ink">{{ $me->name }}</p>
                <p class="text-xs text-body" dir="ltr">{{ __('storefront::storefront.profile.code', ['code' => $me->code]) }}</p>
            </div>
        </div>
        <nav class="flex flex-1 flex-col gap-1">
            @foreach ($menu as [$routeName, $icon, $label, $patterns])
                @php $active = request()->routeIs(...$patterns); @endphp
                <a href="{{ route($routeName) }}" @if ($active) aria-current="page" @endif
                   class="relative flex items-center gap-3 rounded-item px-3 py-2.5 text-sm font-bold transition {{ $active ? 'text-primary' : 'text-body hover:bg-surface-alt hover:text-ink' }}"
                   @if ($active) style="background: color-mix(in srgb, var(--color-primary) 12%, transparent)" @endif>
                    @if ($active)<span class="absolute inset-y-2 start-0 w-1 rounded-full bg-primary" aria-hidden="true"></span>@endif
                    <x-storefront::icon :name="$icon" /> {{ __('storefront::storefront.'.$label) }}
                </a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('site.logout') }}" class="border-t border-line pt-3">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-item px-3 py-2.5 text-sm font-bold text-danger-ink hover:bg-danger/10">
                <x-storefront::icon name="logout" /> {{ __('storefront::storefront.nav.logout') }}
            </button>
        </form>
    </aside>

    <div class="lg:ps-80">
        <header class="sticky top-0 z-30 border-b border-line bg-surface/95 backdrop-blur lg:static lg:border-0 lg:bg-transparent">
            <div class="flex h-16 items-center gap-2 px-4 lg:h-20 lg:px-6">
                <div class="lg:hidden">@include('storefront::partials.logo')</div>
                <a href="{{ route('site.courses') }}" class="sf-btn-ghost ms-auto hidden sm:inline-flex">{{ __('storefront::storefront.nav.courses') }}</a>
                <div class="ms-auto flex items-center gap-1 sm:ms-0">
                    @include('storefront::partials.locale-switch')
                    @include('storefront::partials.theme-toggle')
                    <form method="POST" action="{{ route('site.logout') }}" class="lg:hidden">
                        @csrf
                        <button type="submit" class="grid size-10 place-items-center rounded-full text-danger-ink hover:bg-danger/10" aria-label="{{ __('storefront::storefront.nav.logout') }}"><x-storefront::icon name="logout" /></button>
                    </form>
                </div>
            </div>
        </header>

        <main id="main" class="px-4 pt-6 pb-28 lg:px-6 lg:pb-10">
            @yield('content')
        </main>

        <footer class="flex flex-col items-center gap-3 px-4 pb-24 text-xs text-body lg:pb-6">
            <p>{{ __('storefront::storefront.footer.copyright', ['year' => now()->year, 'name' => $site->platformName]) }}</p>
            @include('storefront::partials.credit')
        </footer>
    </div>

    {{-- الشريط السفلي (موبايل) --}}
    <nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-4 border-t border-line bg-surface pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="{{ __('storefront::storefront.a11y.student_nav') }}">
        @foreach ($bottom as [$routeName, $icon, $label, $patterns])
            @php $active = request()->routeIs(...$patterns); @endphp
            <a href="{{ route($routeName) }}" @if ($active) aria-current="page" @endif class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-bold {{ $active ? 'text-primary' : 'text-body' }}">
                <x-storefront::icon :name="$icon" class="size-6" />
                {{ __('storefront::storefront.'.$label) }}
            </a>
        @endforeach
    </nav>
@endsection
