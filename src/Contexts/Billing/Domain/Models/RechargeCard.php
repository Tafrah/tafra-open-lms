<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Database\Factories\RechargeCardFactory;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * كود شحن واحد: يا إما رصيد للمحفظة، يا إما بيفتح محاضرة (bootcamp_id).
 */
final class RechargeCard extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<RechargeCardFactory> */
    use HasFactory;

    protected $table = 'recharge_cards';

    /** @var list<string> */
    protected $fillable = [
        'recharge_card_group_id',
        'code',
        'amount',
        'currency',
        'bootcamp_id',
        'is_active',
        'used_by',
        'used_at',
        'expiry_date',
    ];

    /** @return BelongsTo<RechargeCardGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(RechargeCardGroup::class, 'recharge_card_group_id');
    }

    /** @return BelongsTo<Bootcamp, $this> */
    public function bootcamp(): BelongsTo
    {
        return $this->belongsTo(Bootcamp::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'used_by');
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    protected static function newFactory(): RechargeCardFactory
    {
        return RechargeCardFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
            'used_at' => 'datetime',
            'expiry_date' => 'datetime',
        ];
    }
}
