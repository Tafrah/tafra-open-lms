<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Billing\Domain\Models\RechargeCard;
use Src\Contexts\Billing\Domain\Models\RechargeCardGroup;

/** @extends Factory<RechargeCard> */
final class RechargeCardFactory extends Factory
{
    protected $model = RechargeCard::class;

    public function definition(): array
    {
        return [
            'recharge_card_group_id' => RechargeCardGroup::factory(),
            'code' => (string) $this->faker->unique()->numerify('1###########'),
            'amount' => 100,
            'is_active' => true,
        ];
    }
}
