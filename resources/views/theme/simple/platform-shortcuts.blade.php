<x-filament-widgets::widget class="ss-shortcuts-widget">
    @if ($tiles !== [])
        <section
            class="shortcut-card"
            aria-labelledby="platform-shortcuts-title"
            x-data="{
                expanded: (() => { try { return localStorage.getItem('fc:shortcuts-expanded') === '1' } catch (e) { return false } })(),
                columns: 3,
                total: {{ count($tiles) }},
                observer: null,
                measure() {
                    // بعد التنقّل (SPA) الشبكة ممكن تكون اتشالت والمراقب لسه بينادي
                    if (! (this.$refs.grid instanceof Element)) return;
                    const tracks = getComputedStyle(this.$refs.grid).gridTemplateColumns.split(' ').filter(Boolean).length;
                    if (tracks > 0) this.columns = tracks;
                },
                init() {
                    this.measure();
                    this.observer = new ResizeObserver(() => this.measure());
                    this.observer.observe(this.$refs.grid);
                },
                destroy() {
                    this.observer?.disconnect();
                },
                get collapsible() { return this.total > this.columns * 2 },
                shown(index) { return this.expanded || ! this.collapsible || index < this.columns * 2 },
                toggle() {
                    this.expanded = ! this.expanded;
                    try { localStorage.setItem('fc:shortcuts-expanded', this.expanded ? '1' : '0') } catch (e) {}
                },
            }"
        >
            <div class="shortcut-card-head">
                <span class="shortcut-card-icon" aria-hidden="true"><x-phosphor-gear-six-bold class="size-[22px]" /></span>
                <div>
                    <h2 id="platform-shortcuts-title" class="shortcut-card-title">{{ __('shell.shortcuts.title') }}</h2>
                    <p class="shortcut-card-subtitle">{{ __('shell.shortcuts.subtitle') }}</p>
                </div>
            </div>

            <div class="shortcut-grid" x-ref="grid">
                @foreach ($tiles as $index => $tile)
                    <a
                        href="{{ $tile['url'] }}"
                        class="shortcut-tile tone-{{ $tile['tone'] }}"
                        x-show="shown({{ $index }})"
                        @if ($tile['badge']) aria-label="{{ $tile['label'] }} — {{ __('shell.shortcuts.pending', ['count' => $tile['badge']]) }}" @endif
                    >
                        <span class="shortcut-tile-icon" aria-hidden="true">
                            <x-dynamic-component :component="$tile['icon']" class="size-6" />
                            @if ($tile['badge'])
                                <span class="shortcut-tile-badge fc-number">{{ $tile['badge'] > 99 ? '99+' : $tile['badge'] }}</span>
                            @endif
                        </span>
                        <span class="shortcut-tile-label">{{ $tile['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="shortcut-card-more" x-show="collapsible" x-cloak>
                <button type="button" class="ss-soft-btn" x-on:click="toggle()" x-bind:aria-expanded="expanded">
                    <span x-show="! expanded">{{ __('shell.shortcuts.show_all', ['count' => count($tiles)]) }}</span>
                    <span x-show="expanded" x-cloak>{{ __('shell.shortcuts.show_less') }}</span>
                </button>
            </div>
        </section>
    @endif
</x-filament-widgets::widget>
