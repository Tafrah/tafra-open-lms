<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Contexts\Students\Domain\Enums\StudentStatus;
use Src\Contexts\Students\Domain\Models\Student;

/** @extends Factory<Student> */
final class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => '01'.$this->faker->unique()->numerify('#########'),
            'parent_phone' => '01'.$this->faker->numerify('#########'),
            'password' => 'password',
            'code' => Str::upper(Str::random(8)),
            'status' => StudentStatus::Active,
        ];
    }

    public function blocked(): self
    {
        return $this->state(fn (): array => ['status' => StudentStatus::Blocked]);
    }
}
