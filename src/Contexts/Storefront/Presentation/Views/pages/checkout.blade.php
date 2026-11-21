@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.checkout.title', ['number' => $invoice->number]))

@section('content')
    @php
        $status = $invoice->status;
        $pendingReceipt = $receipt && $receipt->status === \Src\Contexts\Billing\Domain\Enums\ReceiptStatus::Pending;
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-bold text-body">{{ __('storefront::storefront.checkout.invoice') }} <span dir="ltr">{{ $invoice->number }}</span></p>
                <h1 class="sf-title">{{ $course?->name }}</h1>
            </div>
            <x-storefront::status-badge :status="$status" class="text-sm" />
        </header>

        {{-- حالة الفاتورة --}}
        @if ($status === \Src\Contexts\Billing\Domain\Enums\InvoiceStatus::Paid)
            <div class="sf-card flex flex-col items-center gap-3 p-8 text-center" role="status">
                <span class="grid size-16 place-items-center rounded-full bg-success/15 text-success"><x-storefront::icon name="check" class="size-8" /></span>
                <h2 class="text-xl font-bold text-navy">{{ __('storefront::storefront.checkout.approved_title') }}</h2>
                <p class="text-body">{{ __('storefront::storefront.checkout.approved_body') }}</p>
                @if ($course)
                    <a href="{{ route('site.learn.course', $course->slug) }}" class="sf-btn-primary">{{ __('storefront::storefront.purchase.enter') }}</a>
                @endif
            </div>
        @elseif (in_array($status, [\Src\Contexts\Billing\Domain\Enums\InvoiceStatus::Rejected, \Src\Contexts\Billing\Domain\Enums\InvoiceStatus::Canceled], true))
            <div class="sf-card flex flex-col items-center gap-3 p-8 text-center" role="alert">
                <span class="grid size-16 place-items-center rounded-full bg-danger/15 text-danger"><x-storefront::icon name="x" class="size-8" /></span>
                <h2 class="text-xl font-bold text-navy">{{ __('storefront::storefront.checkout.rejected_title') }}</h2>
                @if ($invoice->reject_reason)
                    <p class="rounded-card bg-danger/10 px-4 py-2 font-bold text-danger-ink">{{ __('storefront::storefront.checkout.reason', ['reason' => $invoice->reject_reason]) }}</p>
                @endif
                <form method="POST" action="{{ route('site.checkout.retry', $invoice->number) }}">
                    @csrf
                    <button type="submit" class="sf-btn-primary">{{ __('storefront::storefront.checkout.retry') }}</button>
                </form>
            </div>
        @else
            @if ($pendingReceipt)
                <div class="flex items-start gap-3 rounded-banner border border-warning/40 bg-warning/10 p-5" role="status">
                    <x-storefront::icon name="clock" class="size-7 shrink-0 text-warning-ink" />
                    <div>
                        <h2 class="font-bold text-navy">{{ __('storefront::storefront.checkout.waiting_title') }}</h2>
                        <p class="mt-1 text-sm text-body">{{ __('storefront::storefront.checkout.waiting_body') }}</p>
                    </div>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-5">
                {{-- الخطوة ١: حوّل --}}
                <section class="sf-card space-y-4 p-5 lg:col-span-3" aria-labelledby="pay-title">
                    <h2 id="pay-title" class="flex items-center gap-2 text-lg font-bold text-navy"><span class="grid size-7 place-items-center rounded-full bg-primary text-sm text-white">1</span> {{ __('storefront::storefront.checkout.step_transfer') }}</h2>
                    <div class="flex items-center justify-between rounded-item bg-primary/10 p-4">
                        <span class="font-bold text-ink">{{ __('storefront::storefront.checkout.amount') }}</span>
                        <span class="text-2xl font-extrabold text-primary">{{ fc_currency((float) $invoice->amount, $invoice->currency) }}</span>
                    </div>
                    @if ((float) $invoice->discount_amount > 0)
                        <p class="text-sm text-body">{{ __('storefront::storefront.checkout.discount_note', ['subtotal' => fc_currency((float) $invoice->subtotal, $invoice->currency), 'discount' => fc_currency((float) $invoice->discount_amount, $invoice->currency)]) }}</p>
                    @endif
                    @forelse ($methods as $method)
                        <div class="rounded-item border border-line p-4" x-data="{ copied: false }">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="font-bold text-navy">{{ $method->name }}</p>
                                    <p class="text-xs text-body">{{ $method->type?->getLabel() }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-card bg-surface-alt px-3 py-1.5 font-mono text-base font-bold tracking-wider" dir="ltr">{{ $method->account_number }}</span>
                                    <button type="button" class="sf-btn-outline px-3 py-1.5" x-on:click="copied = await window.storefrontCopy(@js($method->account_number)); setTimeout(() => copied = false, 2000)"
                                            aria-label="{{ __('storefront::storefront.checkout.copy_number', ['name' => $method->name]) }}">
                                        <x-storefront::icon name="copy" class="size-4" />
                                        <span x-text="copied ? @js(__('storefront::storefront.checkout.copied')) : @js(__('storefront::storefront.checkout.copy'))">{{ __('storefront::storefront.checkout.copy') }}</span>
                                    </button>
                                </div>
                            </div>
                            @if ($method->account_name)
                                <p class="mt-2 text-sm text-body">{{ __('storefront::storefront.checkout.account_name', ['name' => $method->account_name]) }}</p>
                            @endif
                            @if ($method->instructions)
                                <p class="mt-2 whitespace-pre-line rounded-card bg-surface-alt p-3 text-sm leading-6 text-body">{{ $method->instructions }}</p>
                            @endif
                        </div>
                    @empty
                        <x-storefront::empty icon="wallet" :title="__('storefront::storefront.empty.methods_title')" :body="__('storefront::storefront.empty.methods_body')" />
                    @endforelse
                </section>

                {{-- الخطوة ٢: ارفع الإيصال --}}
                <section class="sf-card space-y-4 p-5 lg:col-span-2" aria-labelledby="upload-title">
                    <h2 id="upload-title" class="flex items-center gap-2 text-lg font-bold text-navy"><span class="grid size-7 place-items-center rounded-full bg-primary text-sm text-white">2</span> {{ $pendingReceipt ? __('storefront::storefront.checkout.replace_receipt') : __('storefront::storefront.checkout.step_upload') }}</h2>
                    @if ($methods->isNotEmpty())
                        <form method="POST" action="{{ route('site.checkout.receipt', $invoice->number) }}" enctype="multipart/form-data" class="space-y-4" novalidate>
                            @csrf
                            <div>
                                <label for="payment_method_id" class="sf-label">{{ __('storefront::storefront.fields.payment_method') }}</label>
                                <select id="payment_method_id" name="payment_method_id" required class="sf-input" @error('payment_method_id') aria-invalid="true" @enderror>
                                    @foreach ($methods as $method)
                                        <option value="{{ $method->getKey() }}" @selected((string) old('payment_method_id', $receipt?->payment_method_id) === (string) $method->getKey())>{{ $method->name }} — {{ $method->account_number }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method_id')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="receipt" class="sf-label">{{ __('storefront::storefront.fields.receipt') }}</label>
                                <input id="receipt" name="receipt" type="file" required accept="image/jpeg,image/png,image/webp,application/pdf" class="sf-input file:me-3 file:rounded-card file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:font-bold file:text-primary" @error('receipt') aria-invalid="true" aria-describedby="receipt-error" @enderror>
                                <p class="mt-1 text-xs text-muted">{{ __('storefront::storefront.checkout.receipt_hint', ['size' => fc_number((int) config('storefront.receipt_max_kb') / 1024)]) }}</p>
                                @error('receipt')<p id="receipt-error" class="sf-error" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="sender_phone" class="sf-label">{{ __('storefront::storefront.fields.sender_phone') }}</label>
                                <input id="sender_phone" name="sender_phone" type="tel" inputmode="tel" required value="{{ old('sender_phone', $receipt?->sender_phone) }}" class="sf-input" @error('sender_phone') aria-invalid="true" @enderror>
                                @error('sender_phone')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="reference" class="sf-label">{{ __('storefront::storefront.fields.reference') }} <span class="font-normal text-muted">{{ __('storefront::storefront.fields.optional') }}</span></label>
                                <input id="reference" name="reference" type="text" value="{{ old('reference', $receipt?->transfer_reference) }}" maxlength="64" class="sf-input">
                                @error('reference')<p class="sf-error" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="sf-btn-primary w-full py-3"><x-storefront::icon name="upload" /> {{ __('storefront::storefront.checkout.send_receipt') }}</button>
                        </form>
                    @endif
                </section>
            </div>
        @endif
    </div>
@endsection
