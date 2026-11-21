<div class="text-center">
    @if ($result === 'success')
        <div class="space-y-3 py-2" role="status">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-success/15 text-success"><x-storefront::icon name="check" class="size-8" /></span>
            <p class="font-bold text-success-ink">{{ $message }}</p>
            <button type="button" wire:click="again" class="sf-btn-outline">{{ __('storefront::storefront.wallet.redeem_another') }}</button>
        </div>
    @elseif ($result === 'error')
        <div class="space-y-3 py-2" role="alert">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-danger/15 text-danger"><x-storefront::icon name="x" class="size-8" /></span>
            <p class="font-bold text-danger-ink">{{ $message }}</p>
            <button type="button" wire:click="again" class="sf-btn-outline">{{ __('storefront::storefront.wallet.try_again') }}</button>
        </div>
    @else
        <form wire:submit="redeem" class="space-y-4">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-primary/10 text-primary"><x-storefront::icon name="ticket" class="size-8" /></span>
            <div>
                <p class="text-lg font-bold text-navy">{{ __('storefront::storefront.wallet.redeem_title') }}</p>
                <p class="mt-1 text-sm text-body">{{ __('storefront::storefront.wallet.redeem_body') }}</p>
            </div>
            <div class="relative text-start">
                <label for="redeem-code-{{ $this->getId() }}" class="sr-only">{{ __('storefront::storefront.fields.code') }}</label>
                <span class="pointer-events-none absolute inset-y-0 start-4 grid place-items-center text-muted"><x-storefront::icon name="ticket" /></span>
                <input id="redeem-code-{{ $this->getId() }}" type="text" inputmode="numeric" autocomplete="off" wire:model="code" maxlength="40"
                       placeholder="{{ __('storefront::storefront.wallet.code_placeholder') }}" class="sf-input py-3 ps-12 tracking-widest"
                       @error('code') aria-invalid="true" aria-describedby="redeem-error-{{ $this->getId() }}" @enderror>
                @error('code')<p id="redeem-error-{{ $this->getId() }}" class="sf-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="sf-btn-primary w-full py-3" wire:loading.attr="disabled" wire:target="redeem">
                <span wire:loading.remove wire:target="redeem">{{ __('storefront::storefront.wallet.redeem') }}</span>
                <span wire:loading wire:target="redeem">{{ __('storefront::storefront.actions.loading') }}</span>
            </button>
        </form>
    @endif
</div>
