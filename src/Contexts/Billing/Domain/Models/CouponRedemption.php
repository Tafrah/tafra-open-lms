<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * استخدام كوبون في فاتورة.
 */
final class CouponRedemption extends Model
{
    use BelongsToTenant;

    protected $table = 'coupon_redemptions';

    /** @var list<string> */
    protected $fillable = [
        'coupon_id',
        'student_id',
        'payment_invoice_id',
        'discount_amount',
        'original_amount',
        'final_amount',
        'redeemed_at',
    ];

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<PaymentInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'payment_invoice_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
            'discount_amount' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
        ];
    }
}
