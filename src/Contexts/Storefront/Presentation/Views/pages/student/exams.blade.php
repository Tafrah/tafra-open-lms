@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.nav.exams'))

@section('content')
    <h1 class="sf-title mb-6">{{ __('storefront::storefront.nav.exams') }}</h1>
    @include('storefront::pages.student.partials.attempts-table', ['attempts' => $attempts])
    <div class="mt-6">{{ $attempts->links() }}</div>
@endsection
