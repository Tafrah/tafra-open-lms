<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Billing\Domain\Enums\RechargeCardType;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;

/** @extends Factory<RechargeCardGroup> */
final class RechargeCardGroupFactory extends Factory
{
    protected $model = RechargeCardGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => RechargeCardType::Wallet,
            'amount' => 100,
            'cards_count' => 0,
            'is_active' => true,
        ];
    }
}
