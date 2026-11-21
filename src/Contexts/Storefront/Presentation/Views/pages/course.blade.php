@extends('storefront::layouts.site')

@section('title', $course->name)
@section('description', $course->short_description ?? '')
@if ($course->getFirstMediaUrl('thumbnail'))
    @section('og_image', $course->getFirstMediaUrl('thumbnail'))
@endif

@section('content')
    {{-- شبكة واحدة: الهيدر والمحتوى في عمودين، وكارت الشراء في التالت من فوق (لاصق) زي طفرة.
         خلفية الهيدر ممتدة لعرض الشاشة بـ box-shadow + clip-path. --}}
    <div class="sf-container grid gap-x-8 lg:grid-cols-3">
    <section class="relative bg-canvas py-10 shadow-[0_0_0_100vmax_var(--color-canvas)] [clip-path:inset(0_-100vmax)] lg:col-span-2 lg:py-14">
        <div>
            <nav class="mb-4 text-sm text-body" aria-label="{{ __('storefront::storefront.a11y.breadcrumb') }}">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ route('site.home') }}" class="hover:text-primary">{{ __('storefront::storefront.nav.home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('site.courses') }}" class="hover:text-primary">{{ __('storefront::storefront.nav.courses') }}</a></li>
                    @if ($course->level)
                        <li aria-hidden="true">/</li>
                        <li><a href="{{ route('site.courses', ['level' => $course->level->getKey()]) }}" class="hover:text-primary">{{ $course->level->name }}</a></li>
                    @endif
                </ol>
            </nav>
            <div>
                <div class="space-y-4">
                    @if ($course->level)<span class="sf-badge">{{ $course->level->name }}</span>@endif
                    <h1 class="text-3xl leading-tight font-bold text-navy lg:text-[40px]">{{ $course->name }}</h1>
                    @if ($course->short_description)
                        <p class="text-lg leading-8 text-body">{{ $course->short_description }}</p>
                    @endif
                    <ul class="flex flex-wrap gap-4 text-sm font-bold text-body">
                        <li class="flex items-center gap-1.5"><x-storefront::icon name="play" class="size-5 text-primary" /> {{ trans_choice('storefront::storefront.counts.lessons', $lessonsCount, ['count' => fc_number($lessonsCount)]) }}</li>
                        <li class="flex items-center gap-1.5"><x-storefront::icon name="exam" class="size-5 text-primary" /> {{ trans_choice('storefront::storefront.counts.exams', $examsCount, ['count' => fc_number($examsCount)]) }}</li>
                        @if ($site->teacherName)
                            <li class="flex items-center gap-1.5"><x-storefront::icon name="user" class="size-5 text-primary" /> {{ $site->teacherName }}</li>
                        @endif
                    </ul>
                    @if ($promoId)
                        <div class="aspect-video overflow-hidden rounded-banner border border-line bg-black">
                            <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $promoId }}?rel=0&modestbranding=1" title="{{ __('storefront::storefront.course.promo') }}" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

        <aside class="relative z-10 pt-6 lg:col-start-3 lg:row-span-2 lg:row-start-1 lg:pt-10" aria-label="{{ __('storefront::storefront.course.purchase') }}">
            <div class="lg:sticky lg:top-24">
                <livewire:storefront.purchase-card :bootcamp-id="$course->getKey()" />
            </div>
        </aside>

        <div class="py-10 lg:col-span-2" x-data="{ tab: 'content' }">
            <div class="mb-6 flex gap-2 border-b border-line" role="tablist" aria-label="{{ __('storefront::storefront.course.tabs') }}">
                <button type="button" role="tab" id="tab-content" aria-controls="panel-content" :aria-selected="tab === 'content'" x-on:click="tab = 'content'"
                        class="-mb-px border-b-2 px-4 py-3 text-sm font-bold transition" :class="tab === 'content' ? 'border-primary text-primary' : 'border-transparent text-body hover:text-ink'">{{ __('storefront::storefront.course.tab_content') }}</button>
                <button type="button" role="tab" id="tab-description" aria-controls="panel-description" :aria-selected="tab === 'description'" x-on:click="tab = 'description'"
                        class="-mb-px border-b-2 px-4 py-3 text-sm font-bold transition" :class="tab === 'description' ? 'border-primary text-primary' : 'border-transparent text-body hover:text-ink'">{{ __('storefront::storefront.course.tab_description') }}</button>
            </div>

            <div id="panel-content" role="tabpanel" aria-labelledby="tab-content" x-show="tab === 'content'" class="space-y-3">
                @forelse ($sections as $index => $section)
                    @php
                        $sectionLessons = $section->items->where('type', \Src\Contexts\Academy\Domain\Enums\SectionItemType::Lesson)->count();
                        $sectionExams = $section->items->where('type', \Src\Contexts\Academy\Domain\Enums\SectionItemType::Exam)->count();
                    @endphp
                    <div class="overflow-hidden rounded-banner border border-line bg-surface" x-data="{ open: {{ $index === 0 ? 'true' : 'false' }} }">
                        <h3>
                            <button type="button" class="flex w-full items-center gap-3 p-4 text-start" x-on:click="open = ! open" :aria-expanded="open" aria-controls="section-{{ $section->getKey() }}">
                                <span class="grid size-8 shrink-0 place-items-center rounded-card bg-primary/10 text-primary"><x-storefront::icon name="book" class="size-[18px]" /></span>
                                <span class="flex-1">
                                    <span class="block font-bold text-navy">{{ $section->name }}</span>
                                    <span class="text-xs text-body">{{ trans_choice('storefront::storefront.counts.videos', $sectionLessons, ['count' => fc_number($sectionLessons)]) }} • {{ trans_choice('storefront::storefront.counts.exams', $sectionExams, ['count' => fc_number($sectionExams)]) }}</span>
                                </span>
                                <x-storefront::icon name="chevron-down" class="size-5 text-muted transition" x-bind:class="open && 'rotate-180'" />
                            </button>
                        </h3>
                        <ul id="section-{{ $section->getKey() }}" x-show="open" x-collapse class="border-t border-line">
                            @foreach ($section->items as $item)
                                @php
                                    $preview = $item->is_free_preview || $section->is_free_preview;
                                    $unlocked = $enrolled || $preview;
                                    $icon = $item->type === \Src\Contexts\Academy\Domain\Enums\SectionItemType::Exam ? 'exam' : match ($item->lesson?->type?->value) { 'Pdf' => 'document', 'Text' => 'text', default => 'play' };
                                @endphp
                                <li class="flex items-center gap-3 px-4 py-3 text-sm not-last:border-b not-last:border-line">
                                    <x-storefront::icon :name="$icon" class="size-5 shrink-0 text-primary" />
                                    @if ($unlocked && $student)
                                        <a href="{{ route('site.learn', [$course->slug, $item->getKey()]) }}" class="flex-1 font-bold text-ink hover:text-primary">{{ $item->name }}</a>
                                    @else
                                        <span class="flex-1 font-bold text-ink">{{ $item->name }}</span>
                                    @endif
                                    @if ($preview && ! $enrolled)
                                        <span class="rounded-full bg-success/15 px-2 py-0.5 text-xs font-bold text-success-ink">{{ __('storefront::storefront.course.free_preview') }}</span>
                                    @endif
                                    @if (! $unlocked)
                                        <x-storefront::icon name="lock" class="size-4 text-muted" />
                                        <span class="sr-only">{{ __('storefront::storefront.course.locked') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <x-storefront::empty icon="book" :title="__('storefront::storefront.empty.content_title')" :body="__('storefront::storefront.empty.content_body')" />
                @endforelse
            </div>

            <div id="panel-description" role="tabpanel" aria-labelledby="tab-description" x-show="tab === 'description'" x-cloak class="sf-card sf-prose p-6">
                @if (filled($course->description))
                    {!! app(\Src\Support\Infrastructure\Html\HtmlSanitizer::class)->clean($course->description) !!}
                @else
                    <p class="text-body">{{ $course->short_description ?? __('storefront::storefront.course.no_description') }}</p>
                @endif
            </div>
        </div>

    </div>
@endsection
