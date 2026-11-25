{{-- شريط «نسخة تجربة» أعلى الموقع --}}
<div class="bg-amber-100 px-4 py-1.5 text-center text-xs font-bold text-amber-900 dark:bg-amber-400/15 dark:text-amber-200" role="note">
    {{ __('demo::demo.badge') }} — {{ $resetAt ? __('demo::demo.banner.resets', ['time' => $resetAt]) : __('demo::demo.banner.body') }}
    <a href="{{ url('/admin/login') }}" class="ms-2 underline underline-offset-2">{{ __('demo::demo.banner.admin_link') }}</a>
</div>
