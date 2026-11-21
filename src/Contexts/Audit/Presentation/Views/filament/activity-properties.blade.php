<div class="space-y-2">
    @forelse ($properties as $key => $value)
        <div class="flex flex-wrap items-start gap-2 border-b border-gray-100 pb-2 dark:border-white/10">
            <span class="min-w-32 text-sm font-medium">{{ $key }}</span>
            <span class="fc-code text-sm">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</span>
        </div>
    @empty
        <p class="text-sm text-gray-500">{{ __('common.none') }}</p>
    @endforelse
</div>
