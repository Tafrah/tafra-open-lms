/*
 * الموقع العام للطلبة. Alpine جاي مع Livewire — هنا بس الوضع الليلي
 * والنسخ للحافظة. مفيش مكتبات تقيلة (docs/product/plan.html#landing).
 */
const THEME_KEY = 'storefront-theme';

function readTheme() {
    try {
        return localStorage.getItem(THEME_KEY) || 'system';
    } catch {
        return 'system';
    }
}

function applyTheme(mode) {
    const dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.theme = mode;
}

window.storefrontTheme = {
    current: readTheme,
    set(mode) {
        try {
            localStorage.setItem(THEME_KEY, mode);
        } catch {
            // التخزين مقفول (وضع خاص) — الوضع بيشتغل للصفحة دي بس
        }
        applyTheme(mode);
    },
    cycle() {
        const order = ['light', 'dark', 'system'];
        const next = order[(order.indexOf(readTheme()) + 1) % order.length];
        this.set(next);
        return next;
    },
};

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (readTheme() === 'system') {
        applyTheme('system');
    }
});

applyTheme(readTheme());

window.storefrontCopy = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        return false;
    }
};
