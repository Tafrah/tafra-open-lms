{{-- قاع السايدبار: الإعلانات، المرشد، ومين داخل (ss-foot في طفرة) --}}
@php
    $user = filament()->auth()->user();
    $announcements = \App\Filament\Shell\AdminShell::announcementsUrl();
    $guide = \App\Filament\Shell\AdminShell::guide();
@endphp
@if ($user)
    <div class="ss-foot">
        @if ($announcements)
            <a href="{{ $announcements }}" class="ss-row" title="{{ __('shell.foot.announcements') }}">
                <span class="ss-row-icon"><x-phosphor-bell-bold class="size-[18px]" /></span>
                <span class="ss-row-label" x-show="$store.sidebar.isOpen">{{ __('shell.foot.announcements') }}</span>
            </a>
        @endif

        @if ($guide)
            <a href="{{ filament()->getHomeUrl() }}#onboarding" class="ss-row" title="{{ __('shell.foot.guide') }}">
                <span class="ss-row-icon"><x-phosphor-compass-bold class="size-[18px]" /></span>
                <span class="ss-row-label" x-show="$store.sidebar.isOpen">{{ __('shell.foot.guide') }}</span>
                <span class="ss-progress fc-number" x-show="$store.sidebar.isOpen">{{ $guide['done'] }}/{{ $guide['total'] }}</span>
            </a>
        @endif

        <div class="ss-user">
            <a href="{{ filament()->getProfileUrl() ?? '#' }}" class="ss-avatar">
                <img src="{{ filament()->getUserAvatarUrl($user) }}" alt="">
            </a>
            <div class="ss-user-text" x-show="$store.sidebar.isOpen">
                <span class="ss-user-name">{{ filament()->getUserName($user) }}</span>
                <span class="ss-user-email">{{ $user->email }}</span>
            </div>
            <form method="POST" action="{{ filament()->getLogoutUrl() }}" x-show="$store.sidebar.isOpen">
                @csrf
                <button type="submit" class="ss-icon-btn ss-logout" title="{{ __('shell.foot.logout') }}" aria-label="{{ __('shell.foot.logout') }}">
                    <x-phosphor-sign-out-bold class="ss-flip size-5" />
                </button>
            </form>
        </div>
    </div>
@endif
