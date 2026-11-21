<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * الكورسات اللي الكوبون شغال عليها لما applicable_type = specific.
 */
final class CouponItem extends Model
{
    use BelongsToTenant;

    protected $table = 'coupon_items';

    /** @var list<string> */
    protected $fillable = [
        'coupon_id',
        'item_type',
        'item_id',
    ];

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
