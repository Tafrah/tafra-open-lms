@props(['course', 'progress' => null])
@php
    $thumb = $course->getFirstMediaUrl('thumbnail');
    $level = $course->level;
    $levelColor = $level?->color && preg_match('/^#[0-9A-Fa-f]{6}$/', $level->color) ? $level->color : null;
    $href = $progress !== null ? route('site.learn.course', $course->slug) : route('site.courses.show', $course->slug);
@endphp
<article class="group relative flex h-full flex-col overflow-hidden rounded-banner border border-line bg-surface transition duration-500 hover:-translate-y-2.5 hover:shadow-[0_20px_40px_rgba(0,0,0,.2)]">
    <div class="h-1.5 w-full {{ $levelColor ? '' : 'bg-primary' }}" @if ($levelColor) style="background-color: {{ $levelColor }}" @endif aria-hidden="true"></div>
    <div class="sf-shine aspect-video w-full overflow-hidden bg-tint">
        @if ($thumb)
            <img src="{{ $thumb }}" alt="" loading="lazy" class="size-full object-cover transition duration-700 group-hover:scale-105">
        @else
            <div class="grid size-full place-items-center bg-linear-to-br from-primary to-secondary text-white" aria-hidden="true">
                <x-storefront::icon name="cap" class="size-14 opacity-80" />
            </div>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-3 p-5">
        <div class="flex items-center justify-between gap-2">
            @if ($level)
                <span class="sf-badge">{{ $level->name }}</span>
            @endif
            @if ($course->is_featured)
                <span class="inline-flex items-center gap-1 text-xs font-bold text-gold"><x-storefront::icon name="sparkles" class="size-4" />{{ __('storefront::storefront.course.featured') }}</span>
            @endif
        </div>
        @if ($site->teacherName)
            <div class="flex items-center gap-2 text-sm text-body">
                @if ($site->teacherPhotoUrl)
                    <img src="{{ $site->teacherPhotoUrl }}" alt="" class="size-7 rounded-full object-cover">
                @else
                    <span class="grid size-7 place-items-center rounded-full bg-primary/15 text-xs font-bold text-primary" aria-hidden="true">{{ mb_substr($site->teacherName, 0, 1) }}</span>
                @endif
                <span>{{ $site->teacherName }}</span>
            </div>
        @endif
        <h3 class="text-lg leading-7 font-bold text-navy">
            <a href="{{ $href }}" class="line-clamp-2 bg-linear-to-r from-primary to-primary bg-[length:0%_2px] bg-bottom bg-no-repeat transition-all duration-500 group-hover:bg-[length:100%_2px] focus:outline-none">
                <span class="absolute inset-0" aria-hidden="true"></span>
                {{ $course->name }}
            </a>
        </h3>
        @if ($course->short_description)
            <p class="text-sm leading-6 text-body">{{ \Illuminate\Support\Str::limit($course->short_description, 70) }}</p>
        @endif
        <div class="mt-auto border-t border-line pt-4">
            @if ($progress !== null)
                <div class="flex items-center justify-between text-xs font-bold text-body">
                    <span>{{ __('storefront::storefront.course.progress') }}</span>
                    <span>{{ fc_number($progress) }}%</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface-alt" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('storefront::storefront.course.progress') }}">
                    <div class="h-full rounded-full bg-primary" style="width: {{ $progress }}%"></div>
                </div>
            @else
                <x-storefront::price :course="$course" />
            @endif
        </div>
    </div>
</article>
