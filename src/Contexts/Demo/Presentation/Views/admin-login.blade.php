{{-- صفحة دخول اللوحة في نسخة التجربة: «ادخل كـ…» بضغطة (DemoLoginController::staff) --}}
<section class="fc-demo-login mt-6 border-t border-gray-200 pt-6 dark:border-white/10" aria-labelledby="fc-demo-login-title">
    <div class="mb-3 flex items-center gap-2">
        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-400/15 dark:text-amber-300">{{ __('demo::demo.badge') }}</span>
        <h2 id="fc-demo-login-title" class="text-sm font-bold text-gray-950 dark:text-white">{{ __('demo::demo.login.title') }}</h2>
    </div>

    <div class="grid gap-2">
        @foreach ($accounts as $key => $account)
            <form method="POST" action="{{ route('demo.login.staff', $key) }}">
                @csrf
                <button type="submit" data-demo-login="{{ $key }}"
                        class="group flex w-full items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-start transition hover:border-primary-400 hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-primary-500 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400">
                        <x-dynamic-component :component="$account['icon']" class="size-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-gray-950 dark:text-white">{{ __("demo::demo.accounts.{$key}.label") }}</span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ __("demo::demo.accounts.{$key}.hint") }}</span>
                    </span>
                    <x-phosphor-arrow-left-bold class="size-4 text-gray-400 transition group-hover:text-primary-600 ltr:rotate-180" />
                </button>
            </form>
        @endforeach
    </div>

    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        {{ __('demo::demo.login.password_hint') }}
        <code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-gray-700 dark:bg-white/10 dark:text-gray-200" dir="ltr">{{ $password }}</code>
    </p>
</section>
