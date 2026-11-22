@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.nav.notifications'))

@section('content')
    <div class="space-y-8">
        <h1 class="sf-title">{{ __('storefront::storefront.nav.notifications') }}</h1>

        <section aria-labelledby="my-notifications-title" class="space-y-3">
            <h2 id="my-notifications-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.notifications.mine') }}</h2>
            @if ($items->isEmpty())
                <x-storefront::empty icon="bell" :title="__('storefront::storefront.empty.notifications_title')" :body="__('storefront::storefront.empty.notifications_body')" />
            @else
                <ul class="space-y-3">
                    @foreach ($items as $item)
                        @php
                            $tone = match ($item['tone']) {
                                'success' => 'bg-success/15 text-success-ink',
                                'danger' => 'bg-danger/15 text-danger-ink',
                                default => 'bg-primary/10 text-primary',
                            };
                        @endphp
                        <li>
                            <div class="sf-card relative flex gap-3 p-4 {{ $item['unread'] ? 'border-primary' : '' }}" data-testid="student-notification">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $tone }}" aria-hidden="true"><x-storefront::icon :name="$item['icon']" /></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-bold text-ink">{{ $item['title'] }}</p>
                                        @if ($item['unread'])
                                            <span class="inline-flex items-center rounded-full bg-primary px-2.5 py-0.5 text-xs font-bold text-white">{{ __('storefront::storefront.notifications.new') }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm leading-6 text-body">{{ $item['body'] }}</p>
                                    <p class="mt-1 text-xs text-body">{{ $item['at']?->diffForHumans() }}</p>
                                </div>
                                @if ($item['url'])
                                    <a href="{{ $item['url'] }}" class="self-center text-sm font-bold text-primary hover:underline">{{ __('storefront::storefront.actions.view') }}</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div>{{ $notifications->links() }}</div>
            @endif
        </section>

        <section aria-labelledby="announcements-title" class="space-y-3">
            <h2 id="announcements-title" class="text-lg font-bold text-navy">{{ __('storefront::storefront.notifications.announcements') }}</h2>
            @if ($announcements->isEmpty())
                <x-storefront::empty icon="megaphone" :title="__('storefront::storefront.empty.announcements_title')" :body="__('storefront::storefront.empty.announcements_body')" />
            @else
                <div class="space-y-3">
                    @foreach ($announcements as $announcement)
                        @include('storefront::pages.student.partials.announcement', ['announcement' => $announcement])
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
