<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Application\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Enums\TransactionType;
use Src\Contexts\Billing\Domain\Exceptions\BillingException;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * المحفظة: كل تغيير في الرصيد = صف في `money_transactions` + تحديث `students.balance`
 * في نفس الـ transaction مع قفل على صف الطالب — مفيش سحب مرتين بنفس الرصيد.
 */
final readonly class WalletAction
{
    public function deposit(Student $student, float $amount, TransactionSource $source, ?Model $for = null, ?string $notes = null, ?int $actorId = null): MoneyTransaction
    {
        return $this->move($student, $amount, TransactionType::Deposit, $source, $for, $notes, $actorId);
    }

    public function withdraw(Student $student, float $amount, TransactionSource $source, ?Model $for = null, ?string $notes = null, ?int $actorId = null): MoneyTransaction
    {
        return $this->move($student, $amount, TransactionType::Withdraw, $source, $for, $notes, $actorId);
    }

    private function move(Student $student, float $amount, TransactionType $type, TransactionSource $source, ?Model $for, ?string $notes, ?int $actorId): MoneyTransaction
    {
        throw_if($amount <= 0, BillingException::because('invalid_amount'));

        return DB::transaction(function () use ($student, $amount, $type, $source, $for, $notes, $actorId): MoneyTransaction {
            /** @var Student $locked */
            $locked = Student::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
            $balance = (float) $locked->getAttribute('balance');

            if ($type === TransactionType::Withdraw && $balance + 0.00001 < $amount) {
                throw BillingException::because('insufficient_balance', ['balance' => fc_number($balance)]);
            }

            $after = round($type === TransactionType::Deposit ? $balance + $amount : $balance - $amount, 2);
            $locked->forceFill(['balance' => $after])->save();
            $student->setAttribute('balance', $after);

            return MoneyTransaction::query()->create([
                'uuid' => (string) Str::uuid(),
                'student_id' => $locked->getKey(),
                'amount' => round($amount, 2),
                'type' => $type,
                'source' => $source,
                'transactionable_type' => $for?->getMorphClass(),
                'transactionable_id' => $for?->getKey(),
                'balance_after' => $after,
                'notes' => $notes,
                'created_by' => $actorId,
            ]);
        });
    }
}
