{{-- «ابحث عن صفحة»: بيفلتر صفحات القائمة وانت بتكتب (ss-filter في طفرة) --}}
<div
    class="ss-filter"
    x-data="{
        q: '',
        apply() {
            const term = this.q.trim().toLowerCase();
            const nav = this.$root.closest('.fi-sidebar-nav');
            let any = false;
            nav.querySelectorAll('.fi-sidebar-group').forEach((group) => {
                const label = (group.querySelector('.fi-sidebar-group-label')?.textContent || '').toLowerCase();
                const groupHit = term !== '' && label.includes(term);
                let visible = 0;
                group.querySelectorAll('.fi-sidebar-item').forEach((item) => {
                    const hit = term === '' || groupHit || item.textContent.toLowerCase().includes(term);
                    item.style.display = hit ? '' : 'none';
                    if (hit) visible++;
                });
                group.style.display = visible > 0 ? '' : 'none';
                // مجموعة مطوية بتتفتح وقت البحث عشان النتيجة تبان
                const items = group.querySelector('.fi-sidebar-group-items');
                if (items && term !== '') items.style.display = '';
                if (visible > 0) any = true;
            });
            this.$refs.empty.hidden = any;
        },
    }"
    x-show="$store.sidebar.isOpen"
>
    <x-phosphor-magnifying-glass-bold class="ss-filter-icon size-4" aria-hidden="true" />
    <input
        type="search"
        class="ss-filter-input"
        x-model="q"
        x-on:input.debounce.80ms="apply()"
        x-on:keydown.escape="q = ''; apply()"
        x-on:keydown.enter.prevent="$root.closest('.fi-sidebar-nav').querySelector('.fi-sidebar-item:not([style*=none]) a')?.click()"
        placeholder="{{ __('shell.filter.placeholder') }}"
        aria-label="{{ __('shell.filter.label') }}"
        autocomplete="off"
    >
    <p class="ss-no-results" x-ref="empty" hidden>{{ __('shell.filter.empty') }}</p>
</div>
