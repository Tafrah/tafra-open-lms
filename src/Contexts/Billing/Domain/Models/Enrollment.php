<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Database\Factories\EnrollmentFactory;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\EnrollmentType;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * سجل اشتراك/إلغاء اشتراك طالب في كورس — `bootcamp_enrolled_students_histories` في طفرة.
 * آخر سجل للطالب في الكورس هو الحالة الحالية.
 */
final class Enrollment extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $table = 'bootcamp_enrolled_students_histories';

    /** @var list<string> */
    protected $fillable = [
        'bootcamp_id',
        'student_id',
        'type',
        'source',
        'payment_invoice_id',
        'recharge_card_id',
        'enrolled_by',
        'access_duration_days',
        'expires_at',
    ];

    /** @return BelongsTo<Bootcamp, $this> */
    public function bootcamp(): BelongsTo
    {
        return $this->belongsTo(Bootcamp::class);
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

    /** @return BelongsTo<RechargeCard, $this> */
    public function rechargeCard(): BelongsTo
    {
        return $this->belongsTo(RechargeCard::class, 'recharge_card_id');
    }

    /** @return BelongsTo<User, $this> */
    public function enroller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function isActive(): bool
    {
        return $this->type === EnrollmentType::Enroll
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    protected static function newFactory(): EnrollmentFactory
    {
        return EnrollmentFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => EnrollmentType::class,
            'source' => EnrollmentSource::class,
            'expires_at' => 'datetime',
            'access_duration_days' => 'integer',
        ];
    }
}
