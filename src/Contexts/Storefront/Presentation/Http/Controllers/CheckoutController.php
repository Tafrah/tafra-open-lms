<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\CheckoutAction;
use Src\Contexts\Billing\Application\Actions\SubmitReceiptAction;
use Src\Contexts\Billing\Application\Queries\PaymentMethodUsage;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;
use Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns\ResolvesStudent;
use Src\Support\Domain\Exceptions\DomainException;

/**
 * الشراء: بالمحفظة (فوري)، بالتحويل (فاتورة + إيصال)، أو مجاني.
 * الفاتورة بتتجاب بـ `student_id` الطالب الداخل — فاتورة غيره = 404.
 */
final class CheckoutController
{
    use ResolvesStudent;

    public function __construct(private readonly CheckoutAction $checkout) {}

    public function wallet(Request $request, string $slug): RedirectResponse
    {
        return $this->purchase($request, $this->publishedCourse($slug), PaymentGateway::Wallet);
    }

    public function free(Request $request, string $slug): RedirectResponse
    {
        // ManualTransfer عن قصد: لو السعر بعد الخصم صفر الأكشن بيحوّله «مجاني» بنفسه،
        // ولو مش صفر بيطلع فاتورة تحويل عادية بدل ما يفتح الكورس ببلاش
        return $this->purchase($request, $this->publishedCourse($slug), PaymentGateway::ManualTransfer);
    }

    public function transfer(Request $request, string $slug): RedirectResponse
    {
        return $this->purchase($request, $this->publishedCourse($slug), PaymentGateway::ManualTransfer);
    }

    public function show(string $number): View
    {
        $invoice = $this->invoice($number);

        return view('storefront::pages.checkout', [
            'invoice' => $invoice,
            'course' => $invoice->bootcamp,
            'receipt' => $invoice->receipts->sortByDesc('id')->first(),
            // الرقم اللي وصل حده الشهري مابيظهرش (PaymentMethodUsage)
            'methods' => app(PaymentMethodUsage::class)->availableForCheckout(),
        ]);
    }

    public function receipt(Request $request, string $number, SubmitReceiptAction $submit): RedirectResponse
    {
        $invoice = $this->invoice($number);
        $methodIds = app(PaymentMethodUsage::class)->availableForCheckout()->modelKeys();

        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', Rule::in($methodIds)],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:'.(int) config('storefront.receipt_max_kb', 5120)],
            'sender_phone' => ['required', 'string', 'regex:/^[0-9+\s-]{8,20}$/'],
            'reference' => ['nullable', 'string', 'max:64'],
        ], [], [
            'payment_method_id' => __('storefront::storefront.fields.payment_method'),
            'receipt' => __('storefront::storefront.fields.receipt'),
            'sender_phone' => __('storefront::storefront.fields.sender_phone'),
            'reference' => __('storefront::storefront.fields.reference'),
        ]);

        /** @var PaymentMethod $method */
        $method = PaymentMethod::query()->findOrFail((int) $data['payment_method_id']);

        try {
            $submit->handle(
                $this->student(),
                $invoice,
                $method,
                $request->file('receipt'),
                trim((string) $data['sender_phone']),
                filled($data['reference'] ?? null) ? trim((string) $data['reference']) : null,
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('site.checkout.show', $invoice->number)
            ->with('status', __('storefront::storefront.checkout.receipt_sent'));
    }

    /** فاتورة اترفضت → فاتورة تحويل جديدة لنفس الكورس */
    public function retry(string $number): RedirectResponse
    {
        $invoice = $this->invoice($number);

        abort_unless(in_array($invoice->status, [InvoiceStatus::Rejected, InvoiceStatus::Canceled], true), 404);
        abort_if($invoice->bootcamp === null, 404);

        try {
            $fresh = $this->checkout->handle($this->student(), $invoice->bootcamp, PaymentGateway::ManualTransfer);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('site.checkout.show', $fresh->number);
    }

    private function purchase(Request $request, Bootcamp $course, PaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate(['coupon' => ['nullable', 'string', 'max:64']]);

        try {
            $invoice = $this->checkout->handle($this->student(), $course, $gateway, $data['coupon'] ?? null);
        } catch (DomainException $e) {
            return redirect()->route('site.courses.show', $course->slug)->with('error', $e->getMessage());
        }

        if ($invoice->status === InvoiceStatus::Paid) {
            return redirect()->route('site.learn.course', $course->slug)
                ->with('status', __('storefront::storefront.checkout.enrolled', ['course' => $course->name]));
        }

        return redirect()->route('site.checkout.show', $invoice->number);
    }

    private function invoice(string $number): PaymentInvoice
    {
        $invoice = PaymentInvoice::query()
            ->with(['bootcamp.media', 'receipts.method'])
            ->where('number', $number)
            ->where('student_id', $this->student()->getKey())
            ->first();

        abort_if($invoice === null, 404);

        return $invoice;
    }
}
