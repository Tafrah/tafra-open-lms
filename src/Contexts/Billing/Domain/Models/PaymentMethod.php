<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Src\Contexts\Billing\Database\Factories\PaymentMethodFactory;
use Src\Contexts\Billing\Domain\Enums\PaymentMethodType;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * رقم/حساب المدرس اللي الطالب بيحوّل عليه (فودافون كاش، إنستاباي، بنك…).
 */
final class PaymentMethod extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $table = 'payment_methods';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'type',
        'account_number',
        'account_name',
        'instructions',
        'is_active',
        'order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'account_number', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('billing');
    }

    protected static function newFactory(): PaymentMethodFactory
    {
        return PaymentMethodFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }
}
