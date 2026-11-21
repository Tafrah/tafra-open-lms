@extends('storefront::layouts.site')

@section('title', __('storefront::storefront.courses.title'))

@section('content')
    <section class="bg-canvas py-10">
        <div class="sf-container">
            <h1 class="sf-title">{{ $activeLevel ? __('storefront::storefront.courses.level_title', ['level' => $activeLevel->name]) : __('storefront::storefront.courses.title') }}</h1>
            <p class="mt-2 text-body">{{ __('storefront::storefront.courses.body') }}</p>

            <form method="GET" action="{{ route('site.courses') }}" class="mt-6 grid gap-3 sm:grid-cols-[1fr_16rem_auto]" role="search">
                <div class="relative">
                    <label for="courses-q" class="sr-only">{{ __('storefront::storefront.nav.search') }}</label>
                    <span class="pointer-events-none absolute inset-y-0 start-3 grid place-items-center text-muted"><x-storefront::icon name="search" class="size-4" /></span>
                    <input id="courses-q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('storefront::storefront.nav.search_placeholder') }}" class="sf-input ps-9">
                </div>
                <div>
                    <label for="courses-level" class="sr-only">{{ __('storefront::storefront.courses.level') }}</label>
                    <select id="courses-level" name="level" class="sf-input" onchange="this.form.submit()">
                        <option value="">{{ __('storefront::storefront.courses.all_levels') }}</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level->getKey() }}" @selected($activeLevel?->is($level))>{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="sf-btn-primary">{{ __('storefront::storefront.courses.filter') }}</button>
            </form>

            @if ($levels->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2" aria-label="{{ __('storefront::storefront.courses.level') }}">
                    <a href="{{ route('site.courses', array_filter(['q' => $term])) }}" class="rounded-full border px-4 py-1.5 text-sm font-bold transition {{ $activeLevel === null ? 'border-primary bg-primary text-white' : 'border-line bg-surface text-ink hover:border-primary' }}">{{ __('storefront::storefront.courses.all_levels') }}</a>
                    @foreach ($levels as $level)
                        <a href="{{ route('site.courses', array_filter(['level' => $level->getKey(), 'q' => $term])) }}" class="rounded-full border px-4 py-1.5 text-sm font-bold transition {{ $activeLevel?->is($level) ? 'border-primary bg-primary text-white' : 'border-line bg-surface text-ink hover:border-primary' }}" @if ($activeLevel?->is($level)) aria-current="true" @endif>{{ $level->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="sf-container py-10" aria-live="polite">
        @if ($courses->isEmpty())
            <x-storefront::empty icon="search" :title="__('storefront::storefront.empty.search_title')" :body="__('storefront::storefront.empty.search_body')">
                <a href="{{ route('site.courses') }}" class="sf-btn-outline mt-2">{{ __('storefront::storefront.courses.clear') }}</a>
            </x-storefront::empty>
        @else
            <p class="mb-5 text-sm font-bold text-body">{{ trans_choice('storefront::storefront.counts.courses', $courses->total(), ['count' => fc_number($courses->total())]) }}</p>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <x-storefront::course-card :course="$course" />
                @endforeach
            </div>
            <div class="mt-10">{{ $courses->links() }}</div>
        @endif
    </section>
@endsection
