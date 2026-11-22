{{-- روابط الهيدر السريعة (MenuInner في طفرة) --}}
@php($links = \App\Filament\Shell\AdminShell::quickLinks())
@if ($links !== [])
    <nav class="ss-menu" aria-label="{{ __('shell.topbar.quick_links') }}">
        @foreach ($links as $link)
            <a href="{{ $link['url'] }}" @class(['ss-menu-link', 'is-current' => $link['active']]) @if ($link['active']) aria-current="page" @endif>
                {{ $link['label'] }}
                @if ($link['badge'])
                    <span class="ss-menu-badge fc-number">{{ $link['badge'] > 99 ? '99+' : $link['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
@endif
