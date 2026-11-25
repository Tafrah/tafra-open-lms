@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.nav.wallet'))

@section('content')
    <h1 class="sf-title mb-6">{{ __('storefront::storefront.nav.wallet') }}</h1>
    {{-- بعد شحن كود ناجح: رسالة النجاح تفضل ثانية ونص وبعدين الرصيد والحركات يتحدّثوا --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data x-on:wallet-updated.window="setTimeout(() => window.location.reload(), 1500)">
        <div class="space-y-6">
            <div class="rounded-banner bg-primary p-6 text-white">
                <p class="flex items-center gap-2 text-sm opacity-90"><x-storefront::icon name="wallet" /> {{ __('storefront::storefront.wallet.balance') }}</p>
                <p class="mt-2 text-4xl font-extrabold">{{ fc_currency($balance) }}</p>
            </div>
            <div class="sf-card p-6">
                <livewire:storefront.redeem-code />
                <noscript>
                    <form method="POST" action="{{ route('site.wallet.redeem') }}" class="mt-4 space-y-3">
                        @csrf
                        <label for="code-nojs" class="sf-label">{{ __('storefront::storefront.fields.code') }}</label>
                        <input id="code-nojs" name="code" type="text" class="sf-input" required>
                        <button type="submit" class="sf-btn-primary w-full">{{ __('storefront::storefront.wallet.redeem') }}</button>
                    </form>
                </noscript>
                @error('code')<p class="sf-error mt-3 text-center" role="alert">{{ $message }}</p>@enderror
            </div>
        </div>

        <section class="min-w-0 lg:col-span-2" aria-labelledby="tx-title">
            <h2 id="tx-title" class="mb-4 text-lg font-bold text-navy">{{ __('storefront::storefront.wallet.transactions') }}</h2>
            @if ($transactions->isEmpty())
                <x-storefront::empty icon="wallet" :title="__('storefront::storefront.empty.transactions_title')" :body="__('storefront::storefront.empty.transactions_body')" />
            @else
                <div class="sf-card relative overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-sm">
                        <caption class="sr-only">{{ __('storefront::storefront.wallet.transactions') }}</caption>
                        <thead class="bg-primary/5 text-body">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.wallet.type') }}</th>
                                <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.checkout.amount') }}</th>
                                <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.wallet.source') }}</th>
                                <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.wallet.after') }}</th>
                                <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.exams.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $tx)
                                @php $deposit = $tx->type === \Src\Contexts\Billing\Domain\Enums\TransactionType::Deposit; @endphp
                                <tr class="border-t border-dashed border-line">
                                    <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $deposit ? 'bg-success/15 text-success-ink' : 'bg-danger/15 text-danger-ink' }}">{{ $tx->type->getLabel() }}</span></td>
                                    <td class="px-4 py-3 font-bold {{ $deposit ? 'text-success-ink' : 'text-danger-ink' }}" dir="ltr">{{ ($deposit ? '+' : '−').fc_number((float) $tx->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-body">{{ $tx->source?->getLabel() }}</td>
                                    <td class="px-4 py-3 text-body">{{ fc_currency((float) $tx->balance_after) }}</td>
                                    <td class="px-4 py-3 text-body">{{ fc_date($tx->created_at, 'd M Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">{{ $transactions->links() }}</div>
            @endif
        </section>
    </div>
@endsection
