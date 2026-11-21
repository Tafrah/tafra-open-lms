@php
    $toasts = collect([
        ['type' => 'success', 'message' => session('status')],
        ['type' => 'error', 'message' => session('error')],
    ])->filter(fn (array $t): bool => filled($t['message']))->values();
@endphp
<div class="pointer-events-none fixed inset-x-0 top-4 z-[90] flex flex-col items-center gap-2 px-4" aria-live="polite" role="status"
     x-data="{ toasts: @js($toasts->all()), push(e) { this.toasts.push(e.detail); if (this.toasts.length > 3) this.toasts.shift(); } }"
     x-on:storefront-toast.window="push($event)">
    <template x-for="(toast, index) in toasts" :key="index">
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition.opacity.duration.300ms
             class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-item px-4 py-3 text-sm font-bold text-white shadow-lg"
             :class="toast.type === 'error' ? 'bg-danger' : 'bg-success'">
            <span class="flex-1" x-text="toast.message"></span>
            <button type="button" class="opacity-80 hover:opacity-100" x-on:click="show = false" aria-label="{{ __('storefront::storefront.a11y.close') }}">
                <x-storefront::icon name="x" class="size-4" />
            </button>
        </div>
    </template>
</div>
