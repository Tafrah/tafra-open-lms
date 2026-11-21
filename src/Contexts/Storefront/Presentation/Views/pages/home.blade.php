@extends('storefront::layouts.site')

@section('content')
    {{-- الهيرو --}}
    <section class="sf-pattern relative overflow-hidden bg-canvas" aria-labelledby="hero-title">
        <div class="sf-container grid items-center gap-10 py-12 lg:grid-cols-2 lg:py-20">
            <div class="animate-slide-up space-y-6 text-center lg:text-start">
                @if ($site->subject)
                    <span class="sf-badge text-sm">{{ $site->subject }}</span>
                @endif
                <h1 id="hero-title" class="text-[32px] leading-tight font-bold text-navy lg:text-[42px]">
                    @if ($site->heroHeadline)
                        {{ $site->heroHeadline }}
                    @else
                        <span class="block text-2xl font-bold text-body lg:text-3xl">{{ __('storefront::storefront.hero.discover') }}</span>
                        <span class="relative mt-2 inline-block font-extrabold text-primary">
                            {{ $site->platformName }}
                            <span class="animate-underline absolute inset-x-0 -bottom-2 h-1.5 origin-[inline-start] rounded-full bg-linear-to-l from-primary to-secondary" aria-hidden="true"></span>
                        </span>
                    @endif
                </h1>
                <p class="mx-auto max-w-xl text-lg leading-8 text-body lg:mx-0 lg:text-xl">
                    {{ $site->heroDescription ?? __('storefront::storefront.hero.description', ['subject' => $site->subject ?? __('storefront::storefront.hero.subject_fallback')]) }}
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                    @auth('student')
                        <a href="{{ route('site.my-courses') }}" class="sf-btn-primary px-7 py-3 text-base">{{ __('storefront::storefront.hero.my_courses') }}</a>
                    @else
                        <a href="{{ route('site.register') }}" class="sf-btn-primary px-7 py-3 text-base">{{ __('storefront::storefront.hero.join') }}</a>
                        <a href="{{ route('site.login') }}" class="sf-btn-outline px-7 py-3 text-base">{{ __('storefront::storefront.nav.login') }}</a>
                    @endauth
                </div>
                @php
                    $chips = $site->chips !== [] ? $site->chips : [__('storefront::storefront.hero.chip_1'), __('storefront::storefront.hero.chip_2'), __('storefront::storefront.hero.chip_3')];
                @endphp
                <ul class="flex flex-wrap items-center justify-center gap-2 lg:justify-start" aria-label="{{ __('storefront::storefront.hero.features') }}">
                    @foreach ($chips as $chip)
                        <li class="inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3.5 py-1.5 text-sm font-bold text-ink shadow-sm">
                            <x-storefront::icon name="check-circle" class="size-4 text-success" /> {{ $chip }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="animate-fade-in relative mx-auto w-full max-w-[600px]">
                <div class="absolute -inset-4 -z-0 rounded-[2rem] bg-linear-to-br from-primary/25 to-secondary/20 blur-2xl" aria-hidden="true"></div>
                <div class="relative aspect-[4/3] overflow-hidden rounded-[2rem] border border-line bg-tint">
                    @if ($site->teacherPhotoUrl)
                        <img src="{{ $site->teacherPhotoUrl }}" alt="{{ $site->teacherName ?? $site->platformName }}" class="size-full object-cover" fetchpriority="high">
                    @else
                        <div class="grid size-full place-items-center bg-linear-to-br from-primary to-secondary text-white">
                            <x-storefront::icon name="cap" class="size-28 opacity-90" />
                        </div>
                    @endif
                </div>
                @if ($site->teacherName)
                    <div class="absolute -bottom-5 start-6 flex items-center gap-3 rounded-item border border-line bg-surface px-4 py-3 shadow-lg">
                        <span class="grid size-10 place-items-center rounded-full bg-primary/10 text-primary"><x-storefront::icon name="cap" /></span>
                        <div>
                            <p class="text-sm font-extrabold text-navy">{{ $site->teacherName }}</p>
                            @if ($site->subject)<p class="text-xs text-body">{{ $site->subject }}</p>@endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- الصفوف --}}
    @if ($levels->isNotEmpty())
        <section class="sf-container py-14" aria-labelledby="levels-title">
            <div class="mb-8 text-center">
                <h2 id="levels-title" class="sf-title">{{ __('storefront::storefront.home.levels_title') }}</h2>
                <p class="mt-2 text-body">{{ __('storefront::storefront.home.levels_body') }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($levels as $level)
                    @php $levelColor = $level->color && preg_match('/^#[0-9A-Fa-f]{6}$/', $level->color) ? $level->color : null; @endphp
                    <a href="{{ route('site.courses', ['level' => $level->getKey()]) }}" class="group flex items-center gap-4 rounded-banner border border-line bg-surface p-5 transition hover:-translate-y-1 hover:border-primary hover:shadow-lg">
                        <span class="grid size-14 shrink-0 place-items-center rounded-item text-white {{ $levelColor ? '' : 'bg-primary' }}" @if ($levelColor) style="background-color: {{ $levelColor }}" @endif>
                            <x-storefront::icon name="cap" class="size-7" />
                        </span>
                        <span class="flex-1">
                            <span class="block text-lg font-bold text-navy group-hover:text-primary">{{ $level->name }}</span>
                            <span class="text-sm text-body">{{ trans_choice('storefront::storefront.counts.courses', $level->bootcamps_count, ['count' => fc_number($level->bootcamps_count)]) }}</span>
                        </span>
                        <x-storefront::icon name="chevron-next" class="size-5 text-muted group-hover:text-primary" />
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- كورسات مقترحة --}}
    @if ($featured->isNotEmpty())
        <section class="bg-canvas py-14" aria-labelledby="featured-title">
            <div class="sf-container">
                <div class="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <h2 id="featured-title" class="sf-title">{{ __('storefront::storefront.home.featured_title') }}</h2>
                        <p class="mt-2 text-body">{{ __('storefront::storefront.home.featured_body') }}</p>
                    </div>
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $course)
                        <x-storefront::course-card :course="$course" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- كل الكورسات --}}
    <section class="sf-container py-14" aria-labelledby="courses-title">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="courses-title" class="sf-title">{{ __('storefront::storefront.home.courses_title') }}</h2>
                <p class="mt-2 text-body">{{ __('storefront::storefront.home.courses_body') }}</p>
            </div>
            <a href="{{ route('site.courses') }}" class="sf-btn-outline">{{ __('storefront::storefront.home.all_courses') }} <x-storefront::icon name="chevron-next" class="size-4" /></a>
        </div>
        @if ($courses->isEmpty())
            <x-storefront::empty icon="book" :title="__('storefront::storefront.empty.courses_title')" :body="__('storefront::storefront.empty.courses_body')" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <x-storefront::course-card :course="$course" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- عن المدرس --}}
    @if ($site->aboutText || $site->teacherName)
        <section class="bg-canvas py-14" aria-labelledby="about-title">
            <div class="sf-container grid items-center gap-10 lg:grid-cols-[1fr_2fr]">
                <div class="mx-auto size-56 overflow-hidden rounded-full border-4 border-surface bg-tint shadow-xl lg:size-72">
                    @if ($site->teacherPhotoUrl)
                        <img src="{{ $site->teacherPhotoUrl }}" alt="" loading="lazy" class="size-full object-cover">
                    @else
                        <div class="grid size-full place-items-center text-primary"><x-storefront::icon name="user" class="size-24" /></div>
                    @endif
                </div>
                <div class="space-y-4 text-center lg:text-start">
                    <h2 id="about-title" class="sf-title">{{ __('storefront::storefront.home.about_title', ['name' => $site->teacherName ?? $site->platformName]) }}</h2>
                    <p class="text-lg leading-8 whitespace-pre-line text-body">{{ $site->aboutText ?? __('storefront::storefront.home.about_fallback', ['name' => $site->teacherName ?? $site->platformName]) }}</p>
                    @include('storefront::partials.socials')
                </div>
            </div>
        </section>
    @endif

    {{-- الأرقام --}}
    @if ($stats !== null)
        <section class="sf-container py-14" aria-label="{{ __('storefront::storefront.home.stats_title') }}">
            <dl class="grid gap-4 rounded-banner bg-primary p-6 text-white sm:grid-cols-3 sm:p-10">
                @foreach ([['students', 'users'], ['lessons', 'play'], ['exams', 'exam']] as [$key, $icon])
                    <div class="flex items-center justify-center gap-4 sm:flex-col sm:gap-2">
                        <x-storefront::icon :name="$icon" class="size-9 opacity-80" />
                        <dt class="order-2 text-sm font-bold opacity-90">{{ __('storefront::storefront.home.stats.'.$key) }}</dt>
                        <dd class="text-3xl font-extrabold">+{{ fc_number($stats[$key]) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif
@endsection
