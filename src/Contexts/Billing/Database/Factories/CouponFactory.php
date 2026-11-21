<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Contexts\Billing\Domain\Enums\CouponScope;
use Src\Contexts\Billing\Domain\Enums\CouponType;
use Src\Contexts\Billing\Domain\Models\Coupon;

/** @extends Factory<Coupon> */
final class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'name' => $this->faker->words(2, true),
            'type' => CouponType::Percentage,
            'value' => 20,
            'max_per_user' => 1,
            'applicable_type' => CouponScope::All,
            'is_active' => true,
        ];
    }
}
