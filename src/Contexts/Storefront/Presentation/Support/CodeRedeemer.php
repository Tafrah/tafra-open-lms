<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Support;

use Illuminate\Support\Facades\RateLimiter;
use Src\Contexts\Billing\Application\Actions\RedeemRechargeCardAction;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * «شحن كود» من المحفظة أو من صفحة الكورس — نفس الحد في المكانين:
 * ٥ أكواد غلط لكل طالب في ١٥ دقيقة (`RedeemRechargeCardAction` نفسه
 * سايب الحد لطبقة العرض).
 */
final readonly class CodeRedeemer
{
    public function __construct(private RedeemRechargeCardAction $redeem) {}

    /**
     * @return array{card: RechargeCard, kind: 'wallet'|'bootcamp'}
     *
     * @throws BillingException
     */
    public function redeem(Student $student, string $code): array
    {
        $key = self::key($student);
        $max = (int) config('storefront.limits.redeem_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw BillingException::because('too_many_attempts', ['seconds' => RateLimiter::availableIn($key)]);
        }

        try {
            return $this->redeem->handle($student, $code);
        } catch (BillingException $e) {
            RateLimiter::hit($key, (int) config('storefront.limits.redeem_decay_seconds', 900));

            throw $e;
        }
    }

    public static function key(Student $student): string
    {
        return 'storefront-redeem:'.$student->getKey();
    }
}
