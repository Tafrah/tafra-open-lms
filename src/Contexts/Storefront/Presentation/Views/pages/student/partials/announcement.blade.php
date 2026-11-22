@php
    /** @var \Src\Contexts\Content\Domain\Models\Announcement $announcement */
    $tone = match ($announcement->severity->value) {
        'urgent' => 'bg-danger/15 text-danger-ink',
        'warning' => 'bg-warning/15 text-warning-ink',
        default => 'bg-primary/10 text-primary',
    };
@endphp
<article class="sf-card flex gap-3 p-4" data-testid="announcement">
    <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $tone }}" aria-hidden="true"><x-storefront::icon name="megaphone" /></span>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="font-bold text-ink">{{ $announcement->getTranslation('title', app()->getLocale(), true) }}</h3>
            @if ($announcement->is_pinned)
                <span class="inline-flex items-center rounded-full bg-surface-alt px-2.5 py-0.5 text-xs font-bold text-body">{{ __('storefront::storefront.notifications.pinned') }}</span>
            @endif
        </div>
        <p class="mt-0.5 text-xs text-body">{{ fc_date($announcement->published_at, 'd M Y') }}</p>
        @if (filled($body = $announcement->getTranslation('body', app()->getLocale(), true)))
            <div class="sf-prose mt-2 text-sm">{!! app(\Src\Support\Infrastructure\Html\HtmlSanitizer::class)->clean($body) !!}</div>
        @endif
    </div>
</article>
