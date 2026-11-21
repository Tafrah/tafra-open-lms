<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
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
            'code' => Str::upper(Str::random(12)),
            'amount' => 100,
            'is_active' => true,
        ];
    }
}
