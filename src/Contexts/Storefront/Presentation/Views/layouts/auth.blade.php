@extends('storefront::layouts.base')

@section('robots', 'noindex,follow')
@section('body_class', 'bg-canvas sf-pattern')

@section('body')
    <div class="flex min-h-screen flex-col">
        <div class="sf-container flex items-center justify-between py-4">
            @include('storefront::partials.logo')
            <div class="flex items-center gap-1">
                @include('storefront::partials.locale-switch')
                @include('storefront::partials.theme-toggle')
            </div>
        </div>

        <main id="main" class="sf-container flex flex-1 items-center justify-center py-6">
            <div class="grid w-full max-w-5xl overflow-hidden rounded-[1.75rem] border border-line bg-surface shadow-2xl lg:grid-cols-2">
                {{-- صفحة الأكاديمية --}}
                <div class="relative hidden flex-col justify-between overflow-hidden bg-primary p-10 text-white lg:flex">
                    <div class="absolute inset-0 bg-linear-to-br from-primary to-secondary opacity-90" aria-hidden="true"></div>
                    @if ($site->teacherPhotoUrl)
                        <img src="{{ $site->teacherPhotoUrl }}" alt="" class="absolute inset-0 size-full object-cover opacity-20 mix-blend-luminosity">
                    @endif
                    <div class="relative space-y-4">
                        <span class="inline-grid size-14 place-items-center rounded-item bg-white/15 text-2xl font-extrabold">{{ mb_substr($site->platformName, 0, 1) }}</span>
                        <h2 class="text-3xl font-extrabold">{{ $site->platformName }}</h2>
                        @if ($site->subject || $site->teacherName)
                            <p class="text-lg opacity-90">{{ collect([$site->subject, $site->teacherName])->filter()->implode(' — ') }}</p>
                        @endif
                        <p class="leading-8 opacity-90">{{ $site->heroDescription ?? __('storefront::storefront.auth.side_body') }}</p>
                    </div>
                    <div class="relative space-y-4">
                        @if ($site->socials !== [])
                            <ul class="flex flex-wrap gap-2">
                                @foreach ($site->socials as $network => $url)
                                    <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="grid size-10 place-items-center rounded-full bg-white/15 transition hover:bg-white hover:text-primary" aria-label="{{ __('storefront::storefront.socials.'.$network) }}"><x-storefront::icon :name="$network" class="size-[18px]" /></a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- الفورم --}}
                <div class="p-6 sm:p-10">
                    @yield('form')
                </div>
            </div>
        </main>

        <div class="flex justify-center py-6">@include('storefront::partials.credit')</div>
    </div>
@endsection
