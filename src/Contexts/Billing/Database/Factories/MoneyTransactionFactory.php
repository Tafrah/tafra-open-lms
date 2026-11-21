<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Contexts\Billing\Domain\Enums\TransactionSource;
use Src\Contexts\Billing\Domain\Enums\TransactionType;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Students\Domain\Models\Student;

/** @extends Factory<MoneyTransaction> */
final class MoneyTransactionFactory extends Factory
{
    protected $model = MoneyTransaction::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'student_id' => Student::factory(),
            'amount' => 100,
            'type' => TransactionType::Deposit,
            'source' => TransactionSource::Admin,
        ];
    }
}
