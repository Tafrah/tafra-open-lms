<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Academy\Domain\Models\SectionItemView;
use Src\Contexts\Academy\Domain\Models\StudentItemViewGrant;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Students\Database\Factories\StudentFactory;
use Src\Contexts\Students\Domain\Enums\SchoolType;
use Src\Contexts\Students\Domain\Enums\StudentStatus;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * الطالب — بيدخل الموقع بالموبايل وكلمة السر على حارس `student`
 * (منفصل تمامًا عن مستخدمي لوحة التحكم، فمالوش أي صلاحيات هناك).
 *
 * في طفرة: صف في `users` (user_type = student) + صف في `students`. كلمة السر
 * bcrypt في الاتنين، فبتنقل زي ما هي في التصدير.
 */
final class Student extends Authenticatable
{
    use BelongsToTenant;

    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'students';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'phone',
        'parent_phone',
        'email',
        'password',
        'code',
        'educational_level_id',
        'governorate',
        'school_type',
        'extra_info',
        'status',
        'last_login_at',
    ];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /** @return BelongsTo<EducationalLevel, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(EducationalLevel::class, 'educational_level_id');
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasMany<MoneyTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(MoneyTransaction::class);
    }

    /** @return HasMany<PaymentInvoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    /** @return HasMany<OnlinePassedExam, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(OnlinePassedExam::class);
    }

    /** @return HasMany<SectionItemView, $this> */
    public function views(): HasMany
    {
        return $this->hasMany(SectionItemView::class);
    }

    /** @return HasMany<StudentDevice, $this> */
    public function devices(): HasMany
    {
        return $this->hasMany(StudentDevice::class);
    }

    /** @return HasMany<StudentItemViewGrant, $this> */
    public function viewGrants(): HasMany
    {
        return $this->hasMany(StudentItemViewGrant::class);
    }

    public function isBlocked(): bool
    {
        return $this->status === StudentStatus::Blocked;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'status', 'educational_level_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('students');
    }

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => StudentStatus::class,
            'school_type' => SchoolType::class,
            'extra_info' => 'array',
            'balance' => 'decimal:2',
            'last_login_at' => 'datetime',
        ];
    }
}
