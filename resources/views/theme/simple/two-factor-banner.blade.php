{{-- «احمِ حسابك بالتحقق بخطوتين» — بيظهر على الرئيسية لحد ما يتفعّل أو يتقفل --}}
@php
    $user = filament()->auth()->user();
    $url = \Illuminate\Support\Facades\Gate::allows('access.two_factor')
        ? \Src\Contexts\Identity\Presentation\Filament\Pages\TwoFactorSettings::getUrl()
        : null;
@endphp
{{-- نسخة التجربة: الحساب مشترك بين كل الزوار — مفيش معنى نطلب منهم يفعّلوا التحقق --}}
@if ($user && $url && blank($user->two_factor_confirmed_at) && ! app(\Src\Contexts\Demo\Application\DemoMode::class)->active())
    <div
        class="ss-alert"
        role="status"
        x-data="{ hidden: (() => { try { return localStorage.getItem('fc:2fa-banner') === '1' } catch (e) { return false } })() }"
        x-show="! hidden"
    >
        <x-phosphor-shield-check-bold class="ss-alert-icon size-7" aria-hidden="true" />
        <div class="ss-alert-text">
            <strong>{{ __('shell.two_factor.title') }}</strong>
            <span>{{ __('shell.two_factor.body') }}</span>
        </div>
        <a href="{{ $url }}" class="ss-alert-btn">{{ __('shell.two_factor.action') }}</a>
        <button type="button" class="ss-icon-btn" x-on:click="hidden = true; try { localStorage.setItem('fc:2fa-banner', '1') } catch (e) {}" aria-label="{{ __('shell.two_factor.dismiss') }}">
            <x-phosphor-x-bold class="size-5" />
        </button>
    </div>
@endif
