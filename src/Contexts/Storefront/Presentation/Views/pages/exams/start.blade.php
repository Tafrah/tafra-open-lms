@extends('storefront::layouts.student')

@section('title', $exam->name)

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        @if ($course)
            <a href="{{ route('site.learn', [$course->slug, $item->getKey()]) }}" class="inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline"><x-storefront::icon name="chevron-prev" class="size-4" /> {{ $course->name }}</a>
        @endif

        <section class="sf-card space-y-6 p-6 sm:p-8" aria-labelledby="exam-title">
            <div class="flex items-start gap-4">
                <span class="grid size-14 shrink-0 place-items-center rounded-item bg-primary/10 text-primary"><x-storefront::icon name="exam" class="size-7" /></span>
                <div>
                    <h1 id="exam-title" class="text-2xl font-bold text-navy">{{ $exam->name }}</h1>
                    @if ($exam->description)<p class="mt-2 leading-7 whitespace-pre-line text-body">{{ $exam->description }}</p>@endif
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ([
                    ['exam', __('storefront::storefront.exams.info.questions'), fc_number($questionsCount)],
                    ['clock', __('storefront::storefront.exams.info.duration'), $exam->duration_in_seconds ? trans_choice('storefront::storefront.counts.minutes', intdiv($exam->duration_in_seconds, 60), ['count' => fc_number(intdiv($exam->duration_in_seconds, 60))]) : __('storefront::storefront.exams.info.open_time')],
                    ['flag', __('storefront::storefront.exams.info.chances'), $chancesLeft === null ? __('storefront::storefront.exams.info.unlimited') : fc_number($chancesLeft)],
                    ['check-circle', __('storefront::storefront.exams.info.pass'), fc_number((float) $exam->min_percentage).'%'],
                ] as [$icon, $label, $value])
                    <div class="rounded-item bg-surface-alt p-4 text-center">
                        <x-storefront::icon :name="$icon" class="mx-auto size-6 text-primary" />
                        <dd class="mt-2 text-lg font-extrabold text-navy">{{ $value }}</dd>
                        <dt class="text-xs font-bold text-body">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>

            @if ($denial !== null)
                <p class="rounded-card bg-danger/10 p-4 text-center font-bold text-danger-ink" role="alert">{{ __('academy::academy.errors.'.$denial) }}</p>
            @elseif ($open)
                <a href="{{ route('site.attempts.show', $open->getKey()) }}" class="sf-btn-primary w-full py-3 text-base">{{ __('storefront::storefront.exams.resume') }}</a>
            @elseif ($chancesLeft === 0)
                <p class="rounded-card bg-warning/10 p-4 text-center font-bold text-warning-ink" role="status">{{ __('exams::exams.errors.no_chances_left') }}</p>
            @else
                <ul class="list-disc space-y-1 ps-6 text-sm text-body">
                    <li>{{ __('storefront::storefront.exams.rules.autosave') }}</li>
                    @if ($exam->duration_in_seconds)<li>{{ __('storefront::storefront.exams.rules.autosubmit') }}</li>@endif
                    <li>{{ __('storefront::storefront.exams.rules.one_attempt') }}</li>
                </ul>
                <form method="POST" action="{{ route('site.exams.begin', $item->getKey()) }}">
                    @csrf
                    <button type="submit" class="sf-btn-primary w-full py-3 text-base">{{ __('storefront::storefront.exams.start') }}</button>
                </form>
            @endif
        </section>

        @if ($attempts->where('finished', true)->isNotEmpty())
            <section aria-labelledby="prev-title" class="space-y-3">
                <h2 id="prev-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.exams.previous_attempts') }}</h2>
                @include('storefront::pages.student.partials.attempts-table', ['attempts' => $attempts->where('finished', true)->load('exam')])
            </section>
        @endif
    </div>
@endsection
