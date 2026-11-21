@extends('storefront::layouts.auth')

@section('title', __('storefront::storefront.auth.forgot_title'))

@section('form')
    <div class="space-y-5 text-center">
        <span class="mx-auto grid size-16 place-items-center rounded-full bg-primary/10 text-primary"><x-storefront::icon name="key" class="size-8" /></span>
        <h1 class="text-2xl font-extrabold text-navy">{{ __('storefront::storefront.auth.forgot_title') }}</h1>
        <p class="leading-8 text-body">{{ __('storefront::storefront.auth.forgot_body') }}</p>
        @if ($site->whatsappUrl())
            <a href="{{ $site->whatsappUrl() }}?text={{ rawurlencode(__('storefront::storefront.auth.forgot_message', ['name' => $site->platformName])) }}" target="_blank" rel="noopener noreferrer" class="sf-btn w-full bg-success py-3 text-base text-white hover:opacity-90">
                <x-storefront::icon name="whatsapp" /> {{ __('storefront::storefront.auth.forgot_whatsapp') }}
            </a>
        @else
            <p class="rounded-card bg-surface-alt p-4 text-sm font-bold text-body">{{ __('storefront::storefront.auth.forgot_no_whatsapp') }}</p>
        @endif
        <a href="{{ route('site.login') }}" class="inline-block text-sm font-bold text-primary hover:underline">{{ __('storefront::storefront.auth.back_to_login') }}</a>
    </div>
@endsection
