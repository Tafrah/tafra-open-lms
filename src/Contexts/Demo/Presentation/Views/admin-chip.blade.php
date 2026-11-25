{{-- شارة «نسخة تجربة» في هيدر اللوحة — أيقونة بس، والكلمة على الشاشات الواسعة (الهيدر زحمة) --}}
<span class="fc-demo-chip inline-flex size-9 shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-full bg-amber-100 text-xs font-bold text-amber-800 2xl:size-auto 2xl:px-2.5 2xl:py-1 dark:bg-amber-400/15 dark:text-amber-300"
      title="{{ $resetAt ? __('demo::demo.banner.resets', ['time' => $resetAt]) : __('demo::demo.banner.body') }}">
    <x-phosphor-flask-bold class="size-4" aria-hidden="true" />
    <span class="sr-only 2xl:not-sr-only">{{ __('demo::demo.badge') }}</span>
</span>
