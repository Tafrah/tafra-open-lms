@extends('storefront::layouts.auth')

@section('title', __('storefront::storefront.auth.login_title'))

@section('form')
    <h1 class="text-2xl font-extrabold text-navy">{{ __('storefront::storefront.auth.login_title') }}</h1>
    <p class="mt-2 text-body">{{ __('storefront::storefront.auth.login_body') }}</p>

    <form method="POST" action="{{ route('site.login.store') }}" class="mt-8 space-y-5" novalidate>
        @csrf
        <div>
            <label for="phone" class="sf-label">{{ __('storefront::storefront.fields.phone') }}</label>
            <input id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required value="{{ old('phone') }}" class="sf-input" placeholder="{{ __('storefront::storefront.fields.phone_placeholder') }}"
                   @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror autofocus>
            @error('phone')<p id="phone-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div x-data="{ show: false }">
            <div class="flex items-center justify-between">
                <label for="password" class="sf-label">{{ __('storefront::storefront.fields.password') }}</label>
                <a href="{{ route('site.password.forgot') }}" class="mb-1.5 text-sm font-bold text-primary hover:underline">{{ __('storefront::storefront.auth.forgot_link') }}</a>
            </div>
            <div class="relative">
                <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" autocomplete="current-password" required class="sf-input pe-12"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" class="absolute inset-y-0 end-0 grid w-12 place-items-center text-muted hover:text-ink" x-on:click="show = ! show" :aria-pressed="show" aria-label="{{ __('storefront::storefront.auth.toggle_password') }}">
                    <x-storefront::icon name="key" class="size-5" />
                </button>
            </div>
            @error('password')<p id="password-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm font-bold text-ink">
            <input type="checkbox" name="remember" value="1" class="size-4 rounded border-line text-primary focus:ring-primary"> {{ __('storefront::storefront.auth.remember') }}
        </label>
        <button type="submit" class="sf-btn-primary w-full py-3 text-base">{{ __('storefront::storefront.nav.login') }}</button>
    </form>

    @isset($demo)
        @include('demo::site-login', $demo)
    @endisset

    <p class="mt-6 text-center text-sm text-body">
        {{ __('storefront::storefront.purchase.no_account') }}
        <a href="{{ route('site.register') }}" class="font-bold text-primary hover:underline">{{ __('storefront::storefront.nav.register') }}</a>
    </p>
@endsection
