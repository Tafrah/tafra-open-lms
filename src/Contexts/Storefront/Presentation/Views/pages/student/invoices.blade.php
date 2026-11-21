@extends('storefront::layouts.student')

@section('title', __('storefront::storefront.nav.invoices'))

@section('content')
    <h1 class="sf-title mb-6">{{ __('storefront::storefront.nav.invoices') }}</h1>
    @if ($invoices->isEmpty())
        <x-storefront::empty icon="document" :title="__('storefront::storefront.empty.invoices_title')" :body="__('storefront::storefront.empty.invoices_body')" />
    @else
        <div class="sf-card overflow-x-auto">
            <table class="w-full min-w-[40rem] text-sm">
                <caption class="sr-only">{{ __('storefront::storefront.nav.invoices') }}</caption>
                <thead class="bg-primary/5 text-body">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.checkout.invoice') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.invoices.course') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.checkout.amount') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.invoices.method') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-bold">{{ __('storefront::storefront.exams.status') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('storefront::storefront.actions.view') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <tr class="border-t border-dashed border-line">
                            <td class="px-4 py-3 font-mono text-xs text-body" dir="ltr">{{ $invoice->number }}</td>
                            <td class="px-4 py-3 font-bold text-ink">{{ $invoice->bootcamp?->name }}</td>
                            <td class="px-4 py-3">{{ fc_currency((float) $invoice->amount, $invoice->currency) }}</td>
                            <td class="px-4 py-3 text-body">{{ $invoice->payment_gateway?->getLabel() }}</td>
                            <td class="px-4 py-3">
                                <x-storefront::status-badge :status="$invoice->status" />
                                @if ($invoice->status === \Src\Contexts\Billing\Domain\Enums\InvoiceStatus::Pending && $invoice->receipts->isNotEmpty())
                                    <span class="mt-1 block text-xs text-body">{{ __('storefront::storefront.invoices.under_review') }}</span>
                                @endif
                                @if ($invoice->reject_reason)
                                    <span class="mt-1 block text-xs text-danger-ink">{{ $invoice->reject_reason }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end">
                                @if ($invoice->payment_gateway === \Src\Contexts\Billing\Domain\Enums\PaymentGateway::ManualTransfer)
                                    <a href="{{ route('site.checkout.show', $invoice->number) }}" class="font-bold text-primary hover:underline">{{ __('storefront::storefront.actions.view') }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $invoices->links() }}</div>
    @endif
@endsection
