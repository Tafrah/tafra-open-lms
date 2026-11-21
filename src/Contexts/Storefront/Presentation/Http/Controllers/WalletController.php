<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns\ResolvesStudent;
use Src\Contexts\Storefront\Presentation\Support\CodeRedeemer;

/** المحفظة: الرصيد، شحن كود (مع حد المحاولات الغلط)، وجدول الحركات. */
final class WalletController
{
    use ResolvesStudent;

    public function show(): View
    {
        $student = $this->student();

        return view('storefront::pages.student.wallet', [
            'balance' => (float) $student->balance,
            'transactions' => MoneyTransaction::query()
                ->where('student_id', $student->getKey())
                ->latest('id')
                ->paginate(15),
        ]);
    }

    /** نفس «اشحن» من غير JavaScript */
    public function redeem(Request $request, CodeRedeemer $redeemer): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']], [], ['code' => __('storefront::storefront.fields.code')]);

        try {
            $outcome = $redeemer->redeem($this->student(), (string) $data['code']);
        } catch (BillingException $e) {
            return redirect()->route('site.wallet')->withErrors(['code' => $e->getMessage()]);
        }

        if ($outcome['kind'] === 'bootcamp' && $outcome['card']->bootcamp !== null) {
            return redirect()->route('site.learn.course', $outcome['card']->bootcamp->slug)
                ->with('status', __('storefront::storefront.wallet.redeemed_course', ['course' => $outcome['card']->bootcamp->name]));
        }

        return redirect()->route('site.wallet')
            ->with('status', __('storefront::storefront.wallet.redeemed_wallet', ['amount' => fc_currency((float) $outcome['card']->amount)]));
    }
}
