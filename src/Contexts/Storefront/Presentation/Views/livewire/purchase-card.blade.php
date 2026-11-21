<div class="overflow-hidden rounded-banner border border-line bg-surface shadow-sm">
    @php $thumb = $course->getFirstMediaUrl('thumbnail'); @endphp
    <div class="aspect-video bg-tint">
        @if ($thumb)
            <img src="{{ $thumb }}" alt="" class="size-full object-cover">
        @else
            <div class="grid size-full place-items-center bg-linear-to-br from-primary to-secondary text-white" aria-hidden="true"><x-storefront::icon name="cap" class="size-16" /></div>
        @endif
    </div>

    <div class="space-y-5 p-5">
        {{-- السعر --}}
        <div class="flex flex-wrap items-end gap-3">
            @if ($enrolled)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-success/15 px-3 py-1 text-sm font-extrabold text-success-ink"><x-storefront::icon name="check-circle" class="size-4" /> {{ __('storefront::storefront.purchase.enrolled') }}</span>
            @elseif ($quote !== null && $quote['discount'] > 0)
                <span class="text-3xl font-extrabold text-navy">{{ $total > 0 ? fc_currency($total, $course->price_currency) : __('storefront::storefront.course.free') }}</span>
                <del class="pb-1 text-sm font-bold text-muted">{{ fc_currency($quote['subtotal'], $course->price_currency) }}</del>
            @else
                <x-storefront::price :course="$course" size="lg" />
            @endif
        </div>

        @if (! $enrolled && $course->hasActiveDiscount() && $course->discount_end_date)
            <p class="flex items-center gap-1.5 text-xs font-bold text-warning-ink"><x-storefront::icon name="clock" class="size-4" /> {{ __('storefront::storefront.purchase.offer_ends', ['date' => fc_date($course->discount_end_date, 'd M Y H:i')]) }}</p>
        @endif

        @if ($enrolled)
            <a href="{{ route('site.learn.course', $course->slug) }}" class="sf-btn-primary w-full py-3 text-base">{{ __('storefront::storefront.purchase.enter') }}</a>
        @elseif ($student === null)
            <a href="{{ route('site.login', ['next' => route('site.courses.show', $course->slug, false)]) }}" class="sf-btn-primary w-full py-3 text-base">{{ __('storefront::storefront.purchase.login_to_buy') }}</a>
            <p class="text-center text-sm text-body">{{ __('storefront::storefront.purchase.no_account') }} <a href="{{ route('site.register') }}" class="font-bold text-primary hover:underline">{{ __('storefront::storefront.nav.register') }}</a></p>
        @else
            {{-- الكوبون --}}
            @if (! $course->isFree())
                <div>
                    @if ($appliedCoupon)
                        <div class="flex items-center justify-between rounded-card border border-success/40 bg-success/10 px-3 py-2 text-sm">
                            <span class="font-bold text-success-ink">{{ __('storefront::storefront.purchase.coupon_active', ['code' => $appliedCoupon, 'amount' => fc_currency($quote['discount'] ?? 0, $course->price_currency)]) }}</span>
                            <button type="button" wire:click="removeCoupon" class="font-bold text-danger-ink hover:underline">{{ __('storefront::storefront.purchase.remove') }}</button>
                        </div>
                    @else
                        <form wire:submit="applyCoupon" class="flex gap-2">
                            <label for="coupon-{{ $course->getKey() }}" class="sr-only">{{ __('storefront::storefront.fields.coupon') }}</label>
                            <input id="coupon-{{ $course->getKey() }}" type="text" wire:model="coupon" autocomplete="off" placeholder="{{ __('storefront::storefront.purchase.coupon_placeholder') }}" class="sf-input uppercase" maxlength="64" @error('coupon') aria-invalid="true" aria-describedby="coupon-error" @enderror>
                            <button type="submit" class="sf-btn-outline shrink-0" wire:loading.attr="disabled" wire:target="applyCoupon">{{ __('storefront::storefront.purchase.apply') }}</button>
                        </form>
                    @endif
                    @error('coupon')<p id="coupon-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
                </div>
            @endif

            @if ($total <= 0)
                <form method="POST" action="{{ route('site.checkout.free', $course->slug) }}">
                    @csrf
                    <input type="hidden" name="coupon" value="{{ $appliedCoupon }}">
                    <button type="submit" class="sf-btn-primary w-full py-3 text-base">{{ __('storefront::storefront.purchase.subscribe_free') }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('site.checkout.wallet', $course->slug) }}" class="space-y-1.5">
                    @csrf
                    <input type="hidden" name="coupon" value="{{ $appliedCoupon }}">
                    <button type="submit" class="sf-btn-primary w-full py-3 text-base">
                        <x-storefront::icon name="wallet" /> {{ __('storefront::storefront.purchase.pay_wallet') }}
                    </button>
                    <p class="text-center text-xs font-bold {{ $balance >= $total ? 'text-body' : 'text-danger-ink' }}">{{ __('storefront::storefront.purchase.balance', ['amount' => fc_currency($balance ?? 0, $course->price_currency)]) }}</p>
                </form>
                <form method="POST" action="{{ route('site.checkout.transfer', $course->slug) }}">
                    @csrf
                    <input type="hidden" name="coupon" value="{{ $appliedCoupon }}">
                    <button type="submit" class="sf-btn-outline w-full py-3">
                        <x-storefront::icon name="upload" /> {{ __('storefront::storefront.purchase.pay_transfer') }}
                    </button>
                </form>
            @endif

            <div x-data="{ redeem: false }">
                <button type="button" class="w-full text-center text-sm font-bold text-primary hover:underline" x-on:click="redeem = true">
                    <x-storefront::icon name="ticket" class="inline size-4" /> {{ __('storefront::storefront.purchase.have_code') }}
                </button>
                <div x-show="redeem" x-cloak class="fixed inset-0 z-[70] grid place-items-center p-4" role="dialog" aria-modal="true" aria-labelledby="redeem-title-{{ $course->getKey() }}" x-on:keydown.escape.window="redeem = false">
                    <div class="absolute inset-0 bg-black/50" x-on:click="redeem = false"></div>
                    <div class="relative w-full max-w-md rounded-banner bg-surface p-6 shadow-2xl" x-trap="redeem">
                        <h2 id="redeem-title-{{ $course->getKey() }}" class="sr-only">{{ __('storefront::storefront.wallet.redeem_title') }}</h2>
                        <livewire:storefront.redeem-code :key="'redeem-'.$course->getKey()" :redirect-to="route('site.courses.show', $course->slug, false)" />
                        <button type="button" class="mt-3 w-full text-sm font-bold text-body hover:text-ink" x-on:click="redeem = false">{{ __('storefront::storefront.actions.cancel') }}</button>
                    </div>
                </div>
            </div>
        @endif

        <ul class="space-y-2.5 border-t border-line pt-4 text-sm text-body">
            <li class="flex items-center gap-2"><x-storefront::icon name="play" class="size-5 text-primary" /> {{ trans_choice('storefront::storefront.counts.lessons', $lessons, ['count' => fc_number($lessons)]) }}</li>
            <li class="flex items-center gap-2"><x-storefront::icon name="exam" class="size-5 text-primary" /> {{ trans_choice('storefront::storefront.counts.exams', $exams, ['count' => fc_number($exams)]) }}</li>
            @if ($minutes > 0)
                <li class="flex items-center gap-2"><x-storefront::icon name="clock" class="size-5 text-primary" /> {{ trans_choice('storefront::storefront.counts.minutes', $minutes, ['count' => fc_number($minutes)]) }}</li>
            @endif
            <li class="flex items-center gap-2"><x-storefront::icon name="key" class="size-5 text-primary" />
                {{ $course->can_watch_in_days ? trans_choice('storefront::storefront.purchase.access_days', $course->can_watch_in_days, ['count' => fc_number($course->can_watch_in_days)]) : __('storefront::storefront.purchase.access_forever') }}
            </li>
        </ul>
    </div>
</div>
