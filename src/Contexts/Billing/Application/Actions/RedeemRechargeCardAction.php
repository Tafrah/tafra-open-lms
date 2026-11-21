<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Actions;

use Illuminate\Support\Facades\DB;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * الطالب بيكتب كود الكارت: يا إما رصيد يتضاف للمحفظة، يا إما محاضرة تتفتح.
 * القفل على صف الكارت بيمنع إن نفس الكود يتصرف مرتين في نفس اللحظة.
 * حد المحاولات الغلط بيتفرض في طبقة العرض (RateLimiter) قبل ما نوصل هنا.
 */
final readonly class RedeemRechargeCardAction
{
    public function __construct(
        private WalletAction $wallet,
        private EnrollStudentAction $enroll,
        private BootcampAccess $access,
    ) {}

    /** @return array{card: RechargeCard, kind: 'wallet'|'bootcamp'} */
    public function handle(Student $student, string $code): array
    {
        $code = preg_replace('/\D+/', '', $code) ?? '';
        throw_if($code === '', BillingException::because('card_invalid'));
        throw_if($student->isBlocked(), BillingException::because('student_blocked'));

        return DB::transaction(function () use ($student, $code): array {
            $card = RechargeCard::query()->where('code', $code)->lockForUpdate()->first();

            throw_if($card === null || ! $card->is_active || ! $card->group->is_active, BillingException::because('card_invalid'));
            throw_if($card->isUsed(), BillingException::because('card_used'));
            throw_if($card->isExpired(), BillingException::because('card_expired'));

            if ($card->bootcamp_id !== null) {
                $bootcamp = $card->bootcamp;
                throw_if($bootcamp === null, BillingException::because('card_invalid'));
                // مانحرقش الكارت على كورس الطالب مشترك فيه أصلًا
                throw_if($this->access->has($student, $bootcamp), BillingException::because('already_enrolled'));

                $card->forceFill(['used_by' => $student->getKey(), 'used_at' => now()])->save();
                $this->enroll->handle($student, $bootcamp, EnrollmentSource::RechargeCard, card: $card);

                return ['card' => $card, 'kind' => 'bootcamp'];
            }

            $card->forceFill(['used_by' => $student->getKey(), 'used_at' => now()])->save();
            $this->wallet->deposit($student, (float) $card->amount, TransactionSource::RechargeCard, $card);

            return ['card' => $card, 'kind' => 'wallet'];
        });
    }
}
