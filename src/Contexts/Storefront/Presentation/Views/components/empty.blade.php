@props(['icon' => 'sparkles', 'title', 'body' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 rounded-banner border border-dashed border-line px-6 py-14 text-center']) }}>
    <span class="grid size-16 place-items-center rounded-full bg-primary/10 text-primary"><x-storefront::icon :name="$icon" class="size-8" /></span>
    <h3 class="text-lg font-bold text-navy">{{ $title }}</h3>
    @if ($body)
        <p class="max-w-md text-sm leading-6 text-body">{{ $body }}</p>
    @endif
    {{ $slot }}
</div>
