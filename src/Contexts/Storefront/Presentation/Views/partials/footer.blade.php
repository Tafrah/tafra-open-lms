<footer class="relative mt-20 bg-surface-alt pt-16" aria-labelledby="footer-title">
    <svg class="absolute inset-x-0 -top-px h-12 w-full text-surface" viewBox="0 0 1440 48" preserveAspectRatio="none" aria-hidden="true">
        <path fill="currentColor" d="M0 0h1440v20c-120 18-240 28-360 28S840 38 720 28 480 4 360 4 120 14 0 28Z" />
    </svg>
    <div class="sf-container relative">
        <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
            <div class="max-w-md space-y-3">
                <h2 id="footer-title" class="sr-only">{{ $site->platformName }}</h2>
                @include('storefront::partials.logo')
                @if ($site->footerText)
                    <p class="text-sm leading-7 text-body">{{ $site->footerText }}</p>
                @elseif ($site->subject && $site->teacherName)
                    <p class="text-sm leading-7 text-body">{{ __('storefront::storefront.footer.tagline', ['subject' => $site->subject, 'teacher' => $site->teacherName]) }}</p>
                @endif
            </div>
            <nav class="grid grid-cols-2 gap-x-10 gap-y-2 text-sm font-bold" aria-label="{{ __('storefront::storefront.footer.links') }}">
                <a href="{{ route('site.home') }}" class="text-body hover:text-primary">{{ __('storefront::storefront.nav.home') }}</a>
                <a href="{{ route('site.courses') }}" class="text-body hover:text-primary">{{ __('storefront::storefront.nav.courses') }}</a>
                @auth('student')
                    <a href="{{ route('site.dashboard') }}" class="text-body hover:text-primary">{{ __('storefront::storefront.nav.dashboard') }}</a>
                    <a href="{{ route('site.wallet') }}" class="text-body hover:text-primary">{{ __('storefront::storefront.nav.wallet') }}</a>
                @else
                    <a href="{{ route('site.login') }}" class="text-body hover:text-primary">{{ __('storefront::storefront.nav.login') }}</a>
                    <a href="{{ route('site.register') }}" class="text-body hover:text-primary">{{ __('storefront::storefront.nav.register') }}</a>
                @endauth
            </nav>
            @if ($site->socials !== [])
                <div class="space-y-3">
                    <p class="text-sm font-bold text-ink">{{ __('storefront::storefront.footer.follow') }}</p>
                    @include('storefront::partials.socials')
                </div>
            @endif
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-line py-6 text-sm text-body sm:flex-row">
            <p>{{ __('storefront::storefront.footer.copyright', ['year' => now()->year, 'name' => $site->platformName]) }}</p>
            @include('storefront::partials.credit')
        </div>
    </div>
</footer>
