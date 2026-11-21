@extends('storefront::layouts.base')

@section('robots', 'noindex,nofollow')
@section('body_class', 'bg-canvas')

@section('body')
    <div x-data="{ drawer: false }" class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-40 border-b border-line bg-surface">
            <div class="flex h-16 items-center gap-3 px-4">
                <button type="button" class="grid size-10 place-items-center rounded-full hover:bg-primary/10 lg:hidden" x-on:click="drawer = true" aria-label="{{ __('storefront::storefront.learn.open_outline') }}">
                    <x-storefront::icon name="menu" class="size-6" />
                </button>
                @include('storefront::partials.logo')
                <span class="hidden h-6 w-px bg-line sm:block" aria-hidden="true"></span>
                <p class="hidden min-w-0 truncate font-bold text-navy sm:block">@yield('course_name')</p>
                <div class="ms-auto flex items-center gap-1">
                    <a href="{{ route('site.my-courses') }}" class="sf-btn-ghost hidden sm:inline-flex"><x-storefront::icon name="book" /> {{ __('storefront::storefront.nav.my_courses') }}</a>
                    @include('storefront::partials.theme-toggle')
                </div>
            </div>
            <div class="h-1.5 bg-surface-alt" role="progressbar" aria-valuenow="@yield('progress', 0)" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('storefront::storefront.course.progress') }}">
                <div class="h-full bg-primary transition-all" style="width: @yield('progress', 0)%"></div>
            </div>
        </header>

        <div class="flex flex-1">
            {{-- سايدبار ٥٠٠px (ديسكتوب) / درج (موبايل) --}}
            <aside class="hidden w-[500px] max-w-[40vw] shrink-0 border-e border-line bg-surface lg:block" aria-label="{{ __('storefront::storefront.learn.outline') }}">
                <div class="sticky top-[70px] max-h-[calc(100vh-70px)] overflow-y-auto p-4">@yield('outline')</div>
            </aside>
            <div x-show="drawer" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="{{ __('storefront::storefront.learn.outline') }}">
                <div class="absolute inset-0 bg-black/40" x-on:click="drawer = false"></div>
                <div class="absolute inset-y-0 start-0 w-[90vw] max-w-md overflow-y-auto bg-surface p-4 shadow-2xl" x-trap.noscroll="drawer" x-on:keydown.escape.window="drawer = false">
                    <div class="mb-3 flex justify-end">
                        <button type="button" class="grid size-10 place-items-center rounded-full hover:bg-primary/10" x-on:click="drawer = false" aria-label="{{ __('storefront::storefront.a11y.close') }}"><x-storefront::icon name="x" /></button>
                    </div>
                    @yield('outline')
                </div>
            </div>

            <main id="main" class="min-w-0 flex-1 p-4 lg:p-8">
                @yield('content')
                <div class="mt-12 flex justify-center">@include('storefront::partials.credit')</div>
            </main>
        </div>
    </div>
@endsection
