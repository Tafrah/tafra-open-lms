<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Billing\Domain\Enums\PaymentMethodType;
use Src\Contexts\Billing\Domain\Models\PaymentMethod;

/** @extends Factory<PaymentMethod> */
final class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'name' => 'فودافون كاش',
            'type' => PaymentMethodType::VodafoneCash,
            'account_number' => '01'.$this->faker->numerify('#########'),
            'account_name' => $this->faker->name(),
            'is_active' => true,
        ];
    }
}
