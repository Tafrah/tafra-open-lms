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
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Database\Factories\PaymentInvoiceFactory;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * فاتورة شراء كورس — `payment_invoices` في طفرة (payment_gateway = manual_transfer للإيصال).
 */
final class PaymentInvoice extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<PaymentInvoiceFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $table = 'payment_invoices';

    /** @var list<string> */
    protected $fillable = [
        'number',
        'student_id',
        'bootcamp_id',
        'coupon_id',
        'subtotal',
        'discount_amount',
        'amount',
        'currency',
        'payment_gateway',
        'status',
        'paid_at',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'reject_reason',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Bootcamp, $this> */
    public function bootcamp(): BelongsTo
    {
        return $this->belongsTo(Bootcamp::class);
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<PaymentReceipt, $this> */
    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'reviewed_by'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('billing');
    }

    protected static function newFactory(): PaymentInvoiceFactory
    {
        return PaymentInvoiceFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'payment_gateway' => PaymentGateway::class,
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }
}
