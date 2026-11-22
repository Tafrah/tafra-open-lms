{{-- شمس/قمر واحد بدل قايمة الثيم (ThemeModeSwitcher في طفرة) --}}
@if (filament()->hasDarkMode() && filament()->hasDarkModeForced() === false)
    <button
        type="button"
        class="ss-nav-icon-btn"
        x-data="{}"
        x-on:click="$dispatch('theme-changed', $store.theme === 'dark' ? 'light' : 'dark')"
        x-bind:aria-label="$store.theme === 'dark' ? @js(__('shell.topbar.theme_light')) : @js(__('shell.topbar.theme_dark'))"
        x-bind:title="$store.theme === 'dark' ? @js(__('shell.topbar.theme_light')) : @js(__('shell.topbar.theme_dark'))"
    >
        <span x-show="$store.theme !== 'dark'"><x-phosphor-sun-bold class="size-5" /></span>
        <span x-show="$store.theme === 'dark'" x-cloak><x-phosphor-moon-bold class="size-5" /></span>
    </button>
@endif
