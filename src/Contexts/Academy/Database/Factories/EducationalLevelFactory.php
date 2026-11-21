<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;

/** @extends Factory<EducationalLevel> */
final class EducationalLevelFactory extends Factory
{
    protected $model = EducationalLevel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['الصف الأول الثانوي', 'الصف الثاني الثانوي', 'الصف الثالث الثانوي']),
            'code' => Str::upper(Str::random(6)),
            'color' => '#419DF1',
            'order' => $this->faker->numberBetween(1, 3),
        ];
    }
}
