<?php

declare(strict_types=1);

namespace Src\Contexts\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Billing\Domain\Enums\EnrollmentType;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Students\Domain\Models\Student;

/** @extends Factory<Enrollment> */
final class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'bootcamp_id' => Bootcamp::factory(),
            'student_id' => Student::factory(),
            'type' => EnrollmentType::Enroll,
            'source' => EnrollmentSource::Admin,
        ];
    }
}
