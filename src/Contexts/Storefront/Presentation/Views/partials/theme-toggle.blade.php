<button type="button"
        x-data="{ mode: document.documentElement.dataset.theme || 'system' }"
        x-on:click="mode = window.storefrontTheme.cycle()"
        class="grid size-10 place-items-center rounded-full text-ink transition hover:bg-primary/10 hover:text-primary"
        :aria-label="mode === 'light' ? @js(__('storefront::storefront.theme.light')) : (mode === 'dark' ? @js(__('storefront::storefront.theme.dark')) : @js(__('storefront::storefront.theme.system')))"
        :title="mode === 'light' ? @js(__('storefront::storefront.theme.light')) : (mode === 'dark' ? @js(__('storefront::storefront.theme.dark')) : @js(__('storefront::storefront.theme.system')))">
    <span x-show="mode === 'light'"><x-storefront::icon name="sun" /></span>
    <span x-show="mode === 'dark'" x-cloak><x-storefront::icon name="moon" /></span>
    <span x-show="mode === 'system'" x-cloak><x-storefront::icon name="system" /></span>
</button>
