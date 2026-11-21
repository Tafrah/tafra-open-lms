{{-- «مطوّر بواسطة طفرة» — على كل صفحة، مش إعداد (docs/product/plan.html#funnel) --}}
<a href="{{ config('storefront.tafra_url') }}" target="_blank" rel="noopener"
   class="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3 py-1.5 text-xs font-bold text-body transition hover:border-tafra hover:text-tafra"
   data-tafra-credit>
    <span class="grid size-5 place-items-center rounded-md bg-tafra text-[11px] font-extrabold leading-none text-white" aria-hidden="true">{{ __('storefront::storefront.credit.mark') }}</span>
    <span>{{ __('storefront::storefront.credit.developed_by') }}</span>
    <span class="text-tafra">{{ __('storefront::storefront.credit.tafra') }}</span>
</a>
