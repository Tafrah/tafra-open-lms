<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Exams\Domain\Models\OnlineExam;

/** @extends Factory<OnlineExam> */
final class OnlineExamFactory extends Factory
{
    protected $model = OnlineExam::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'allowed_chances' => 2,
            'duration_in_seconds' => 1800,
            'min_percentage' => 50,
            'published' => true,
        ];
    }
}
