<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Students\Domain\Models\Student;

/** @extends Factory<OnlinePassedExam> */
final class OnlinePassedExamFactory extends Factory
{
    protected $model = OnlinePassedExam::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'online_exam_id' => OnlineExam::factory(),
            'current_student_chance' => 1,
            'grading_status' => GradingStatus::Graded,
            'start_at' => now(),
        ];
    }
}
