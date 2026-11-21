<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Events\AttemptGraded;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Identity\Domain\Models\User;

/** المدرس/المساعد بيدّي درجة للإجابة المقالية + ملاحظة اختيارية. */
final readonly class GradeEssayAnswerAction
{
    public function __construct(
        private RecalculateAttemptAction $recalculate,
        private Dispatcher $events,
    ) {}

    public function handle(StudentExamAnswer $answer, float $mark, ?string $feedback, User $actor): StudentExamAnswer
    {
        Gate::forUser($actor)->authorize('grade', $answer);

        $max = (float) $answer->max_question_mark;
        throw_if($mark < 0 || $mark > $max, ExamsException::because('mark_out_of_range', ['max' => fc_number($max)]));

        $attempt = DB::transaction(function () use ($answer, $mark, $feedback, $actor, $max) {
            $answer->forceFill([
                'student_awarded_mark' => round($mark, 2),
                'is_correct' => $mark >= $max,
                'grading_status' => GradingStatus::Graded,
                'graded_by' => $actor->getKey(),
                'graded_at' => now(),
                'grader_feedback' => filled($feedback) ? $feedback : null,
            ])->save();

            $attempt = $answer->attempt()->lockForUpdate()->firstOrFail();

            return $this->recalculate->handle($attempt);
        });

        if ($attempt->grading_status === GradingStatus::Graded) {
            $this->events->dispatch(new AttemptGraded((int) $attempt->getKey()));
        }

        return $answer;
    }
}
