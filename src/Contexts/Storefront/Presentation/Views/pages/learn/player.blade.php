@extends('storefront::layouts.player')

@use('Src\Contexts\Academy\Domain\Enums\LessonType')
@use('Src\Contexts\Academy\Domain\Enums\SectionItemType')

@php
    $itemIcon = fn ($item): string => $item->type === SectionItemType::Exam ? 'exam' : match ($item->lesson?->type) { LessonType::Pdf => 'document', LessonType::Text => 'text', default => 'play' };
@endphp

@section('title', $current->name)
@section('course_name', $course->name)
@section('progress', $progress)

@section('outline')
    <div class="space-y-3">
        <a href="{{ route('site.courses.show', $course->slug) }}" class="block rounded-banner bg-primary/10 p-4">
            <span class="block text-xs font-bold text-primary">{{ __('storefront::storefront.learn.course') }}</span>
            <span class="font-bold text-navy">{{ $course->name }}</span>
            <span class="mt-2 block text-xs font-bold text-body">{{ __('storefront::storefront.learn.progress', ['percent' => fc_number($progress)]) }}</span>
        </a>
        @foreach ($sections as $section)
            @php $hasCurrent = $section->items->contains(fn ($i) => $i->is($current)); @endphp
            <div class="rounded-banner border border-line" x-data="{ open: {{ $hasCurrent ? 'true' : 'false' }} }">
                <h2>
                    <button type="button" class="flex w-full items-center gap-3 p-3 text-start" x-on:click="open = ! open" :aria-expanded="open">
                        <span class="grid size-9 shrink-0 place-items-center rounded-item bg-linear-to-br from-primary to-secondary text-white"><x-storefront::icon name="book" class="size-[18px]" /></span>
                        <span class="flex-1 text-sm font-bold text-navy">{{ $section->name }}</span>
                        <x-storefront::icon name="chevron-down" class="size-4 text-muted transition" x-bind:class="open && 'rotate-180'" />
                    </button>
                </h2>
                <ul x-show="open" x-collapse class="space-y-1 px-2 pb-2">
                    @foreach ($section->items as $item)
                        @php
                            $state = $states[$item->getKey()];
                            $isCurrent = $item->is($current);
                            $locked = $state['denial'] !== null;
                        @endphp
                        <li>
                            <a href="{{ route('site.learn', [$course->slug, $item->getKey()]) }}" @if ($isCurrent) aria-current="page" @endif
                               class="flex items-center gap-2.5 rounded-card px-3 py-2.5 text-sm transition {{ $isCurrent ? 'bg-primary text-white' : 'text-ink hover:bg-surface-alt' }}">
                                <x-storefront::icon :name="$itemIcon($item)" class="size-[18px] shrink-0 {{ $isCurrent ? '' : 'text-primary' }}" />
                                <span class="flex-1 font-bold">{{ $item->name }}</span>
                                @if ($item->is_free_preview || $section->is_free_preview)
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $isCurrent ? 'bg-white/20' : 'bg-success/15 text-success-ink' }}">{{ __('storefront::storefront.course.free_preview') }}</span>
                                @endif
                                @if ($state['completed'])
                                    <x-storefront::icon name="check-circle" class="size-5 shrink-0 {{ $isCurrent ? '' : 'text-success' }}" />
                                    <span class="sr-only">{{ __('storefront::storefront.learn.done') }}</span>
                                @elseif ($locked)
                                    <x-storefront::icon name="lock" class="size-4 shrink-0 opacity-70" />
                                    <span class="sr-only">{{ __('storefront::storefront.course.locked') }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
@endsection

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <p class="text-sm font-bold text-primary">{{ $current->section?->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-navy">{{ $current->name }}</h1>
        </div>

        @if ($denial !== null)
            <div class="sf-card flex flex-col items-center gap-4 p-10 text-center" role="alert">
                <span class="grid size-16 place-items-center rounded-full bg-primary/10 text-primary"><x-storefront::icon name="lock" class="size-8" /></span>
                <p class="text-lg font-bold text-navy">{{ __('academy::academy.errors.'.$denial) }}</p>
                @if ($denial === 'not_enrolled')
                    <a href="{{ route('site.courses.show', $course->slug) }}" class="sf-btn-primary">{{ __('storefront::storefront.learn.subscribe') }}</a>
                @endif
            </div>
        @elseif ($current->type === SectionItemType::Exam)
            @php $exam = $current->exam; @endphp
            <div class="sf-card flex flex-col items-center gap-4 p-10 text-center">
                <span class="grid size-16 place-items-center rounded-full bg-primary/10 text-primary"><x-storefront::icon name="exam" class="size-8" /></span>
                <p class="text-lg font-bold text-navy">{{ $exam?->name ?? $current->name }}</p>
                <a href="{{ route('site.exams.start', $current->getKey()) }}" class="sf-btn-primary">{{ __('storefront::storefront.exams.go_to_exam') }}</a>
            </div>
        @elseif ($youtubeId)
            <div class="aspect-video overflow-hidden rounded-banner bg-black shadow-lg">
                <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?rel=0&modestbranding=1&playsinline=1" title="{{ $current->name }}" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
        @elseif ($documentUrl)
            <div class="overflow-hidden rounded-banner border border-line bg-surface">
                <iframe class="h-[75vh] w-full" src="{{ $documentUrl }}" title="{{ $current->name }}"></iframe>
            </div>
            <a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="sf-btn-outline"><x-storefront::icon name="document" /> {{ __('storefront::storefront.learn.open_pdf') }}</a>
        @elseif ($lesson?->type === LessonType::Text)
            <article class="sf-card sf-prose p-6 lg:p-8">
                {!! app(\Src\Support\Infrastructure\Html\HtmlSanitizer::class)->clean($lesson->text) !!}
            </article>
        @else
            <x-storefront::empty icon="alert" :title="__('storefront::storefront.learn.unavailable_title')" :body="__('storefront::storefront.learn.unavailable_body')" />
        @endif

        @if ($denial === null && $lesson?->description)
            <p class="leading-8 whitespace-pre-line text-body">{{ $lesson->description }}</p>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-6">
            @if ($previous)
                <a href="{{ route('site.learn', [$course->slug, $previous->getKey()]) }}" class="sf-btn-outline rounded-xl"><x-storefront::icon name="chevron-prev" class="size-4" /> {{ __('storefront::storefront.learn.previous') }}</a>
            @else
                <span></span>
            @endif

            @if ($denial === null && $current->type === SectionItemType::Lesson)
                @if ($states[$current->getKey()]['completed'])
                    <span class="inline-flex items-center gap-1.5 font-bold text-success-ink"><x-storefront::icon name="check-circle" /> {{ __('storefront::storefront.learn.done') }}</span>
                @else
                    <form method="POST" action="{{ route('site.learn.complete', [$course->slug, $current->getKey()]) }}">
                        @csrf
                        <button type="submit" class="sf-btn rounded-xl border-2 border-success bg-surface text-success-ink hover:bg-success hover:text-white"><x-storefront::icon name="check" /> {{ __('storefront::storefront.learn.mark_done') }}</button>
                    </form>
                @endif
            @endif

            @if ($next)
                <a href="{{ route('site.learn', [$course->slug, $next->getKey()]) }}" class="sf-btn-primary rounded-xl">{{ __('storefront::storefront.learn.next') }} <x-storefront::icon name="chevron-next" class="size-4" /></a>
            @endif
        </div>
    </div>
@endsection
