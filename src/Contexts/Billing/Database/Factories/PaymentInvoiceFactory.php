<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Domain\Enums\InvoiceStatus;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Students\Domain\Models\Student;

/** @extends Factory<PaymentInvoice> */
final class PaymentInvoiceFactory extends Factory
{
    protected $model = PaymentInvoice::class;

    public function definition(): array
    {
        return [
            'number' => 'INV-'.Str::upper(Str::random(8)),
            'student_id' => Student::factory(),
            'bootcamp_id' => Bootcamp::factory(),
            'subtotal' => 150,
            'discount_amount' => 0,
            'amount' => 150,
            'payment_gateway' => PaymentGateway::ManualTransfer,
            'status' => InvoiceStatus::Pending,
        ];
    }
}
