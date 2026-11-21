@php
    $logoUrl = filled($academy->logo_path ?? null) ? \Illuminate\Support\Facades\Storage::disk(app(\Src\Support\Application\Contracts\DiskResolver::class)->for('branding'))->url($academy->logo_path) : null;
@endphp
<a href="{{ route('site.home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="{{ $site->platformName }}">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="" class="h-10 w-auto">
    @else
        <span class="grid size-10 place-items-center rounded-item bg-primary text-lg font-extrabold text-white" aria-hidden="true">{{ mb_substr($site->platformName, 0, 1) }}</span>
    @endif
    <span class="text-base font-extrabold text-navy sm:text-lg">{{ $site->platformName }}</span>
</a>
