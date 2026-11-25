{{-- صفحة دخول الطالب في نسخة التجربة: ادخل كطالب بضغطة --}}
<div class="mt-6 rounded-card border border-dashed border-amber-400 bg-amber-50 p-4 dark:bg-amber-400/10">
    <p class="text-sm font-bold text-ink">{{ __('demo::demo.login.student_title') }}</p>
    <p class="mt-1 text-sm text-body">{{ __('demo::demo.accounts.student.hint') }}</p>
    <form method="POST" action="{{ route('demo.login.student') }}" class="mt-3">
        @csrf
        <button type="submit" data-demo-login="student" class="sf-btn-primary w-full py-2.5">{{ __('demo::demo.login.as_student') }}</button>
    </form>
    <p class="mt-2 text-xs text-muted">
        {{ __('demo::demo.login.student_credentials') }}
        <span dir="ltr" class="font-mono">{{ $phone }} / {{ $password }}</span>
    </p>
</div>
