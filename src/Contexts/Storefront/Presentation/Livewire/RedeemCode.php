<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Storefront\Presentation\Livewire\Concerns\ResolvesStorefrontTenant;
use Src\Contexts\Storefront\Presentation\Support\CodeRedeemer;
use Src\Contexts\Students\Domain\Models\Student;

/** مودال/كارت «اشحن كود»: دايرة تذكرة، الحقل، ونتيجة واضحة (نجاح أخضر / غلط أحمر). */
final class RedeemCode extends Component
{
    use ResolvesStorefrontTenant;

    public string $code = '';

    #[Locked]
    public ?string $redirectTo = null;

    #[Locked]
    public ?string $result = null;

    #[Locked]
    public ?string $message = null;

    public function mount(?string $redirectTo = null): void
    {
        // مسار نسبي بس — مفيش redirect مفتوح لدومين تاني
        $this->redirectTo = is_string($redirectTo) && str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//') ? $redirectTo : null;
    }

    public function redeem(): void
    {
        $student = Auth::guard('student')->user();
        abort_unless($student instanceof Student, 403);

        $this->validate(
            ['code' => ['required', 'string', 'max:40']],
            [],
            ['code' => __('storefront::storefront.fields.code')],
        );

        try {
            $outcome = app(CodeRedeemer::class)->redeem($student, $this->code);
        } catch (BillingException $e) {
            $this->result = 'error';
            $this->message = $e->getMessage();

            return;
        }

        $this->code = '';
        $this->result = 'success';
        $this->message = $outcome['kind'] === 'wallet'
            ? __('storefront::storefront.wallet.redeemed_wallet', ['amount' => fc_currency((float) $outcome['card']->amount)])
            : __('storefront::storefront.wallet.redeemed_course', ['course' => (string) $outcome['card']->bootcamp?->name]);

        $this->dispatch('wallet-updated');

        if ($outcome['kind'] === 'bootcamp' && $outcome['card']->bootcamp !== null) {
            session()->flash('status', $this->message);
            $this->redirectRoute('site.learn.course', $outcome['card']->bootcamp->slug);

            return;
        }

        if ($this->redirectTo !== null) {
            session()->flash('status', $this->message);
            $this->redirect($this->redirectTo);
        }
    }

    public function again(): void
    {
        $this->result = null;
        $this->message = null;
    }

    public function render(): View
    {
        return view('storefront::livewire.redeem-code');
    }
}
