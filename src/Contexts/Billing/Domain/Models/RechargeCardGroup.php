<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Database\Factories\RechargeCardGroupFactory;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * دفعة أكواد شحن («سنتر المعادي - نوفمبر») — `recharge_card_groups` في طفرة.
 */
final class RechargeCardGroup extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<RechargeCardGroupFactory> */
    use HasFactory;

    use LogsActivity;

    protected $table = 'recharge_card_groups';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'type',
        'amount',
        'bootcamp_id',
        'cards_count',
        'expiry_date',
        'is_active',
        'creator_id',
    ];

    /** @return HasMany<RechargeCard, $this> */
    public function cards(): HasMany
    {
        return $this->hasMany(RechargeCard::class);
    }

    /** @return BelongsTo<Bootcamp, $this> */
    public function bootcamp(): BelongsTo
    {
        return $this->belongsTo(Bootcamp::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /** القيمة اللي بتتطبع على الكارت: اسم المحاضرة أو المبلغ */
    public function valueLabel(): string
    {
        $bootcamp = $this->getRelationValue('bootcamp');

        return $bootcamp instanceof Bootcamp
            ? (string) $bootcamp->getAttribute('name')
            : fc_number((float) $this->amount).' '.__('academy::academy.currency');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'amount', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('billing');
    }

    protected static function newFactory(): RechargeCardGroupFactory
    {
        return RechargeCardGroupFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => RechargeCardType::class,
            'amount' => 'decimal:2',
            'expiry_date' => 'datetime',
            'is_active' => 'boolean',
            'cards_count' => 'integer',
        ];
    }
}
