@php
    $student = auth('student')->user();
@endphp
<header x-data="{ open: false }" class="sticky top-0 z-50 border-b border-line bg-surface/95 shadow-sm backdrop-blur">
    <nav class="sf-container flex h-16 items-center gap-3 lg:h-20" aria-label="{{ __('storefront::storefront.a11y.main_nav') }}">
        @include('storefront::partials.logo')

        <div class="ms-6 hidden items-center gap-1 lg:flex">
            @if ($navLevels->isNotEmpty())
                <div class="relative" x-data="{ levels: false }" x-on:click.outside="levels = false" x-on:keydown.escape="levels = false">
                    <button type="button" class="sf-btn-ghost" x-on:click="levels = ! levels" :aria-expanded="levels" aria-haspopup="true">
                        {{ __('storefront::storefront.nav.levels') }}
                        <x-storefront::icon name="chevron-down" class="size-4 transition" x-bind:class="levels && 'rotate-180'" />
                    </button>
                    <div x-show="levels" x-transition x-cloak class="absolute start-0 top-full mt-2 w-64 rounded-item border border-line bg-surface p-2 shadow-xl">
                        @foreach ($navLevels as $level)
                            <a href="{{ route('site.courses', ['level' => $level->getKey()]) }}" class="flex items-center justify-between rounded-card px-3 py-2.5 text-sm font-bold text-ink hover:bg-primary/10 hover:text-primary">
                                <span>{{ $level->name }}</span>
                                <span class="text-xs text-muted">{{ trans_choice('storefront::storefront.counts.courses', $level->bootcamps_count, ['count' => fc_number($level->bootcamps_count)]) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            <a href="{{ route('site.courses') }}" class="sf-btn-ghost @if (request()->routeIs('site.courses*')) text-primary @endif">{{ __('storefront::storefront.nav.courses') }}</a>
        </div>

        <form method="GET" action="{{ route('site.courses') }}" class="relative ms-auto hidden max-w-xs flex-1 md:block" role="search">
            <label for="nav-search" class="sr-only">{{ __('storefront::storefront.nav.search') }}</label>
            <span class="pointer-events-none absolute inset-y-0 start-3 grid place-items-center text-muted"><x-storefront::icon name="search" class="size-4" /></span>
            <input id="nav-search" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('storefront::storefront.nav.search_placeholder') }}" class="sf-input rounded-full py-2 ps-9">
        </form>

        <div class="ms-auto flex items-center gap-1 md:ms-0">
            <div class="hidden sm:block">@include('storefront::partials.locale-switch')</div>
            @include('storefront::partials.theme-toggle')

            @if ($student)
                <div class="relative" x-data="{ menu: false }" x-on:click.outside="menu = false" x-on:keydown.escape="menu = false">
                    <button type="button" class="flex items-center gap-2 rounded-full p-1 pe-3 transition hover:bg-primary/10" x-on:click="menu = ! menu" :aria-expanded="menu" aria-haspopup="true">
                        <span class="grid size-9 place-items-center rounded-full bg-primary font-bold text-white" aria-hidden="true">{{ mb_substr($student->name, 0, 1) }}</span>
                        <span class="hidden max-w-32 truncate text-sm font-bold sm:block">{{ \Illuminate\Support\Str::words($student->name, 2, '') }}</span>
                    </button>
                    <div x-show="menu" x-transition x-cloak class="absolute end-0 top-full mt-2 w-56 rounded-item border border-line bg-surface p-2 shadow-xl">
                        @foreach ([
                            ['site.dashboard', 'grid', 'nav.dashboard'],
                            ['site.my-courses', 'book', 'nav.my_courses'],
                            ['site.exams', 'exam', 'nav.exams'],
                            ['site.wallet', 'wallet', 'nav.wallet'],
                            ['site.profile', 'user', 'nav.profile'],
                        ] as [$routeName, $icon, $label])
                            <a href="{{ route($routeName) }}" class="flex items-center gap-2.5 rounded-card px-3 py-2.5 text-sm font-bold text-ink hover:bg-primary/10 hover:text-primary">
                                <x-storefront::icon :name="$icon" class="size-5" /> {{ __('storefront::storefront.'.$label) }}
                            </a>
                        @endforeach
                        <form method="POST" action="{{ route('site.logout') }}" class="mt-1 border-t border-line pt-1">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-card px-3 py-2.5 text-sm font-bold text-danger-ink hover:bg-danger/10">
                                <x-storefront::icon name="logout" class="size-5" /> {{ __('storefront::storefront.nav.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('site.login') }}" class="sf-btn-ghost hidden sm:inline-flex">{{ __('storefront::storefront.nav.login') }}</a>
                <a href="{{ route('site.register') }}" class="sf-btn-primary hidden sm:inline-flex">{{ __('storefront::storefront.nav.register') }}</a>
            @endif

            <button type="button" class="grid size-10 place-items-center rounded-full hover:bg-primary/10 lg:hidden" x-on:click="open = true" aria-label="{{ __('storefront::storefront.a11y.open_menu') }}" :aria-expanded="open">
                <x-storefront::icon name="menu" class="size-6" />
            </button>
        </div>
    </nav>

    {{-- درج الموبايل --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('storefront::storefront.a11y.main_nav') }}">
        <div class="absolute inset-0 bg-black/40" x-on:click="open = false" x-transition.opacity></div>
        <div class="absolute inset-y-0 start-0 flex w-80 max-w-[85vw] flex-col gap-2 overflow-y-auto bg-surface p-5 shadow-2xl" x-transition x-trap.noscroll="open" x-on:keydown.escape.window="open = false">
            <div class="mb-3 flex items-center justify-between">
                @include('storefront::partials.logo')
                <button type="button" class="grid size-10 place-items-center rounded-full hover:bg-primary/10" x-on:click="open = false" aria-label="{{ __('storefront::storefront.a11y.close') }}">
                    <x-storefront::icon name="x" />
                </button>
            </div>
            <form method="GET" action="{{ route('site.courses') }}" role="search">
                <label for="drawer-search" class="sr-only">{{ __('storefront::storefront.nav.search') }}</label>
                <input id="drawer-search" type="search" name="q" placeholder="{{ __('storefront::storefront.nav.search_placeholder') }}" class="sf-input">
            </form>
            <a href="{{ route('site.courses') }}" class="rounded-card px-3 py-3 font-bold hover:bg-primary/10">{{ __('storefront::storefront.nav.courses') }}</a>
            @foreach ($navLevels as $level)
                <a href="{{ route('site.courses', ['level' => $level->getKey()]) }}" class="rounded-card px-3 py-2.5 ps-6 text-sm font-bold text-body hover:bg-primary/10">{{ $level->name }}</a>
            @endforeach
            <div class="mt-auto flex flex-col gap-2 border-t border-line pt-4">
                @include('storefront::partials.locale-switch')
                @guest('student')
                    <a href="{{ route('site.login') }}" class="sf-btn-outline w-full">{{ __('storefront::storefront.nav.login') }}</a>
                    <a href="{{ route('site.register') }}" class="sf-btn-primary w-full">{{ __('storefront::storefront.nav.register') }}</a>
                @endguest
            </div>
        </div>
    </div>
</header>
