@extends('storefront::layouts.base')

@section('body')
    @include('storefront::partials.navbar')

    <main id="main" class="min-h-[60vh]">
        @yield('content')
    </main>

    @include('storefront::partials.footer')
@endsection
