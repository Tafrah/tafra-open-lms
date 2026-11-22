<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Src\Contexts\Billing\Database\Factories\PaymentReceiptFactory;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;
use Src\Support\Infrastructure\Persistence\Concerns\InteractsWithResolvedMedia;

/**
 * إيصال التحويل اللي الطالب رفعه (صورة) — بيتراجع من المدرس أو المساعد.
 */
final class PaymentReceipt extends Model implements HasMedia
{
    use BelongsToTenant;

    /** @use HasFactory<PaymentReceiptFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use InteractsWithResolvedMedia;

    protected $table = 'payment_receipts';

    /** @var list<string> */
    protected $fillable = [
        'payment_invoice_id',
        'student_id',
        'payment_method_id',
        'amount',
        'sender_phone',
        'transfer_reference',
        'file_hash',
        'status',
    ];

    /** @return BelongsTo<PaymentInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class, 'payment_invoice_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
    }

    protected static function newFactory(): PaymentReceiptFactory
    {
        return PaymentReceiptFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ReceiptStatus::class,
            'amount' => 'decimal:2',
        ];
    }
}
