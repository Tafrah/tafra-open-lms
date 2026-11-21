<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Domain\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Src\Contexts\Billing\Database\Factories\MoneyTransactionFactory;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Enums\TransactionType;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Support\Infrastructure\Persistence\Concerns\BelongsToTenant;

/**
 * حركة في محفظة الطالب — `money_transactions` في طفرة. المبلغ موجب والاتجاه في type.
 */
final class MoneyTransaction extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<MoneyTransactionFactory> */
    use HasFactory;

    protected $table = 'money_transactions';

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'student_id',
        'amount',
        'currency',
        'type',
        'source',
        'transactionable_type',
        'transactionable_id',
        'balance_after',
        'notes',
        'created_by',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return MorphTo<Model, $this> */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): MoneyTransactionFactory
    {
        return MoneyTransactionFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'source' => TransactionSource::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }
}
