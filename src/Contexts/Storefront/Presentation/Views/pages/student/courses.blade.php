@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.nav.my_courses'))

@section('content')
    <h1 class="sf-title mb-6">{{ __('storefront::storefront.nav.my_courses') }}</h1>
    @if ($courses->isEmpty())
        <x-storefront::empty icon="book" :title="__('storefront::storefront.empty.my_courses_title')" :body="__('storefront::storefront.empty.my_courses_body')">
            <a href="{{ route('site.courses') }}" class="sf-btn-primary mt-2">{{ __('storefront::storefront.home.all_courses') }}</a>
        </x-storefront::empty>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($courses as $course)
                <x-storefront::course-card :course="$course" :progress="$course->progress" />
            @endforeach
        </div>
    @endif
@endsection
