<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Billing\Domain\Enums\ReceiptStatus;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Billing\Domain\Models\PaymentReceipt;
use Src\Contexts\Students\Domain\Models\Student;

/** @extends Factory<PaymentReceipt> */
final class PaymentReceiptFactory extends Factory
{
    protected $model = PaymentReceipt::class;

    public function definition(): array
    {
        return [
            'payment_invoice_id' => PaymentInvoice::factory(),
            'student_id' => Student::factory(),
            'amount' => 150,
            'sender_phone' => '01'.$this->faker->numerify('#########'),
            'status' => ReceiptStatus::Pending,
        ];
    }
}
