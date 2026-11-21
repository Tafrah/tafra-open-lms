<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\CheckoutAction;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Storefront\Application\Queries\CourseOutline;
use Src\Contexts\Storefront\Presentation\Livewire\Concerns\ResolvesStorefrontTenant;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Domain\Exceptions\DomainException;

/**
 * كارت الشراء اللاصق في صفحة الكورس: السعر، الكوبون (سعر حي من
 * `CheckoutAction::quote`)، وأزرار الدفع. الأزرار نفسها فورمز عادية
 * (POST + CSRF) عشان تشتغل من غير JS وتتختبر بـ HTTP.
 */
final class PurchaseCard extends Component
{
    use ResolvesStorefrontTenant;

    private const COUPON_ATTEMPTS_PER_MINUTE = 10;

    #[Locked]
    public int $bootcampId;

    public string $coupon = '';

    #[Locked]
    public ?string $appliedCoupon = null;

    public function mount(int $bootcampId): void
    {
        $this->bootcampId = $bootcampId;
    }

    public function applyCoupon(): void
    {
        $student = $this->student();

        if ($student === null) {
            $this->redirectRoute('site.login', ['next' => route('site.courses.show', $this->bootcamp()->slug, false)]);

            return;
        }

        $this->validate(
            ['coupon' => ['required', 'string', 'max:64']],
            [],
            ['coupon' => __('storefront::storefront.fields.coupon')],
        );

        $key = 'storefront-coupon:'.$student->getKey();

        if (RateLimiter::tooManyAttempts($key, self::COUPON_ATTEMPTS_PER_MINUTE)) {
            $this->addError('coupon', __('billing::billing.errors.too_many_attempts', ['seconds' => RateLimiter::availableIn($key)]));

            return;
        }

        RateLimiter::hit($key, 60);

        try {
            app(CheckoutAction::class)->quote($student, $this->bootcamp(), $this->coupon);
        } catch (DomainException $e) {
            $this->appliedCoupon = null;
            $this->addError('coupon', $e->getMessage());

            return;
        }

        $this->appliedCoupon = mb_strtoupper(trim($this->coupon));
        $this->dispatch('storefront-toast', type: 'success', message: __('storefront::storefront.purchase.coupon_applied'));
    }

    public function removeCoupon(): void
    {
        $this->appliedCoupon = null;
        $this->coupon = '';
        $this->resetErrorBag();
    }

    #[On('wallet-updated')]
    public function refreshAfterRedeem(): void
    {
        app(BootcampAccess::class)->forget();
    }

    public function render(): View
    {
        $bootcamp = $this->bootcamp();
        $student = $this->student();
        $enrolled = $student !== null && app(BootcampAccess::class)->has($student, $bootcamp);
        $quote = null;

        if ($student !== null && ! $enrolled) {
            try {
                $quote = app(CheckoutAction::class)->quote($student, $bootcamp, $this->appliedCoupon);
            } catch (DomainException $e) {
                // الكوبون بطل يشتغل (خلص/انتهى) بين الطلبين
                $this->appliedCoupon = null;
                $this->addError('coupon', $e->getMessage());
                $quote = app(CheckoutAction::class)->quote($student, $bootcamp);
            }
        }

        $outline = app(CourseOutline::class);
        $sections = $outline->sections($bootcamp);

        return view('storefront::livewire.purchase-card', [
            'course' => $bootcamp,
            'student' => $student,
            'enrolled' => $enrolled,
            'quote' => $quote,
            'total' => $quote['total'] ?? $bootcamp->currentPrice(),
            'balance' => $student !== null ? (float) $student->fresh()?->balance : null,
            'lessons' => $outline->countOf($sections, SectionItemType::Lesson),
            'exams' => $outline->countOf($sections, SectionItemType::Exam),
            'minutes' => intdiv($outline->durationSeconds($sections), 60),
        ]);
    }

    private function bootcamp(): Bootcamp
    {
        $bootcamp = Bootcamp::query()->published()->with('media')->find($this->bootcampId);

        abort_if($bootcamp === null, 404);

        return $bootcamp;
    }

    private function student(): ?Student
    {
        $student = Auth::guard('student')->user();

        return $student instanceof Student ? $student : null;
    }
}
