@if ($site->socials !== [])
    <ul class="flex flex-wrap items-center gap-2" aria-label="{{ __('storefront::storefront.footer.follow') }}">
        @foreach ($site->socials as $network => $url)
            <li>
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                   class="grid size-10 place-items-center rounded-full border border-line bg-surface text-ink transition hover:-translate-y-0.5 hover:border-primary hover:bg-primary hover:text-white"
                   aria-label="{{ __('storefront::storefront.socials.'.$network) }}">
                    <x-storefront::icon :name="$network" class="size-[18px]" />
                </a>
            </li>
        @endforeach
    </ul>
@endif
