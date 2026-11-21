@extends('storefront::layouts.base')

@section('title', $exam->name)
@section('robots', 'noindex,nofollow')
@section('body_class', 'bg-canvas')

@section('body')
    <header class="border-b border-line bg-surface">
        <div class="sf-container flex h-14 items-center justify-between">
            @include('storefront::partials.logo')
            @include('storefront::partials.theme-toggle')
        </div>
    </header>
    <main id="main" class="sf-container py-6">
        <livewire:storefront.exam-runner :attempt-id="$attempt->getKey()" />
        <div class="mt-12 flex justify-center">@include('storefront::partials.credit')</div>
    </main>
@endsection
