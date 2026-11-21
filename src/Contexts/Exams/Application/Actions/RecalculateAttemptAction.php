<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;

/** يحسب الدرجة والنسبة من الإجابات، والحالة «مستني تصحيح» لو فيه مقالي لسه. */
final readonly class RecalculateAttemptAction
{
    public function handle(OnlinePassedExam $attempt): OnlinePassedExam
    {
        $answers = StudentExamAnswer::query()->where('online_passed_exam_id', $attempt->getKey())->get();
        $total = (float) $answers->sum(fn (StudentExamAnswer $a): float => (float) $a->student_awarded_mark);
        $max = (float) $attempt->max_total_mark;
        $pending = $answers->contains(fn (StudentExamAnswer $a): bool => $a->grading_status === GradingStatus::Pending);

        $attempt->forceFill([
            'student_total_mark' => round($total, 2),
            'student_percentage' => $max > 0 ? round($total / $max * 100, 2) : 0,
            'grading_status' => $pending ? GradingStatus::Pending : GradingStatus::Graded,
            'graded_at' => $pending ? null : ($attempt->graded_at ?? now()),
        ])->save();

        return $attempt;
    }
}
