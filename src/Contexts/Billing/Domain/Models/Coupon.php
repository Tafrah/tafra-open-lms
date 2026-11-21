<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Src\Contexts\Billing\Database\Factories\CouponFactory;
use Src\Contexts\Billing\Domain\Enums\CouponScope;
use Src\Contexts\Billing\Domain\Enums\CouponType;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * كوبون خصم — `coupons` في طفرة (النوع percentage أو fixed).
 */
final class Coupon extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $table = 'coupons';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'max_discount_amount',
        'max_redemptions',
        'max_per_user',
        'current_redemptions',
        'applicable_type',
        'starts_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    /** @return HasMany<CouponItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CouponItem::class);
    }

    /** @return HasMany<CouponRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** قيمة الخصم على سعر معيّن (مايعدّيش السعر نفسه) */
    public function discountFor(float $price): float
    {
        $discount = $this->type === CouponType::Percentage
            ? round($price * ((float) $this->value) / 100, 2)
            : (float) $this->value;

        if ($this->max_discount_amount !== null) {
            $discount = min($discount, (float) $this->max_discount_amount);
        }

        return max(0.0, min($discount, $price));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'type', 'value', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('billing');
    }

    protected static function newFactory(): CouponFactory
    {
        return CouponFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'applicable_type' => CouponScope::class,
            'value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'max_redemptions' => 'integer',
            'max_per_user' => 'integer',
            'current_redemptions' => 'integer',
        ];
    }
}
