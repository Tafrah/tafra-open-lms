<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Queries;

use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Domain\Enums\CouponScope;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\CouponRedemption;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * الكوبون ينفع للطالب ده على الكورس ده؟ بيرمي سبب واضح لو لأ.
 */
final class CouponValidator
{
    public function validate(string $code, Student $student, Bootcamp $bootcamp): Coupon
    {
        $coupon = Coupon::query()->where('code', mb_strtoupper(trim($code)))->first();

        throw_if($coupon === null || ! $coupon->is_active, BillingException::because('coupon_invalid'));
        throw_if($coupon->starts_at !== null && $coupon->starts_at->isFuture(), BillingException::because('coupon_not_started'));
        throw_if($coupon->expires_at !== null && $coupon->expires_at->isPast(), BillingException::because('coupon_expired'));
        throw_if(
            $coupon->max_redemptions !== null && $coupon->current_redemptions >= $coupon->max_redemptions,
            BillingException::because('coupon_exhausted'),
        );

        if ($coupon->max_per_user !== null) {
            $used = CouponRedemption::query()
                ->where('coupon_id', $coupon->getKey())
                ->where('student_id', $student->getKey())
                ->count();

            throw_if($used >= $coupon->max_per_user, BillingException::because('coupon_already_used'));
        }

        if ($coupon->applicable_type === CouponScope::Specific) {
            $applies = $coupon->items()
                ->where('item_type', 'bootcamp')
                ->where('item_id', $bootcamp->getKey())
                ->exists();

            throw_unless($applies, BillingException::because('coupon_not_for_course'));
        }

        return $coupon;
    }
}
