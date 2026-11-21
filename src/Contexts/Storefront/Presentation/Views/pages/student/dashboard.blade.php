@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.nav.dashboard'))

@section('content')
    <div class="space-y-8">
        <section class="relative overflow-hidden rounded-banner bg-primary p-6 text-white sm:p-8" aria-labelledby="welcome-title">
            <div class="absolute -end-10 -top-10 size-48 rounded-full bg-white/10" aria-hidden="true"></div>
            <div class="absolute -bottom-16 end-24 size-40 rounded-full bg-white/10" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 id="welcome-title" class="text-2xl font-extrabold sm:text-3xl">{{ __('storefront::storefront.dashboard.welcome', ['name' => \Illuminate\Support\Str::words($student->name, 1, '')]) }}</h1>
                    <p class="mt-2 opacity-90">{{ __('storefront::storefront.dashboard.welcome_body') }}</p>
                </div>
                <a href="{{ route('site.wallet') }}" class="flex items-center gap-3 rounded-item bg-white/15 px-5 py-3 transition hover:bg-white/25">
                    <x-storefront::icon name="wallet" class="size-7" />
                    <span>
                        <span class="block text-xs opacity-90">{{ __('storefront::storefront.wallet.balance') }}</span>
                        <span class="text-xl font-extrabold">{{ fc_currency((float) $student->balance) }}</span>
                    </span>
                </a>
            </div>
        </section>

        @if ($pendingInvoices->isNotEmpty())
            <section aria-labelledby="pending-title" class="space-y-3">
                <h2 id="pending-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.dashboard.pending_invoices') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($pendingInvoices as $invoice)
                        <a href="{{ route('site.checkout.show', $invoice->number) }}" class="sf-card flex items-center gap-3 p-4 transition hover:border-warning">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-warning/15 text-warning-ink"><x-storefront::icon name="clock" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-bold text-ink">{{ $invoice->bootcamp?->name }}</span>
                                <span class="text-sm text-body">{{ fc_currency((float) $invoice->amount, $invoice->currency) }}</span>
                            </span>
                            <x-storefront::status-badge :status="$invoice->status" />
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="my-courses-title" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 id="my-courses-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.nav.my_courses') }}</h2>
                <a href="{{ route('site.my-courses') }}" class="text-sm font-bold text-primary hover:underline">{{ __('storefront::storefront.actions.view_all') }}</a>
            </div>
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
        </section>

        <section aria-labelledby="results-title" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 id="results-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.dashboard.recent_results') }}</h2>
                <a href="{{ route('site.exams') }}" class="text-sm font-bold text-primary hover:underline">{{ __('storefront::storefront.actions.view_all') }}</a>
            </div>
            @include('storefront::pages.student.partials.attempts-table', ['attempts' => $attempts])
        </section>
    </div>
@endsection
