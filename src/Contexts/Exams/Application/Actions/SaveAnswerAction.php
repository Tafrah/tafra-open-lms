<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Illuminate\Support\Facades\DB;
use Src\Contexts\Exams\Application\Services\OpenAttemptGuard;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * حفظ إجابة سؤال واحد أثناء الامتحان (بيتنادى مع كل اختيار — مفيش إجابة بتضيع لو النت قطع).
 */
final readonly class SaveAnswerAction
{
    public function __construct(private OpenAttemptGuard $guard) {}

    public function handle(Student $student, OnlinePassedExam $attempt, int $questionId, ?int $choiceId = null, ?string $text = null): StudentExamAnswer
    {
        $this->guard->assertWritable($student, $attempt, $questionId);

        /** @var Question $question */
        $question = Question::query()->findOrFail($questionId);

        if ($question->type === QuestionType::MultipleChoice) {
            throw_if(
                $choiceId !== null && ! $question->choices()->whereKey($choiceId)->exists(),
                ExamsException::because('choice_invalid'),
            );
            $answer = ['choice_id' => $choiceId];
        } else {
            $answer = ['text' => mb_substr(trim((string) $text), 0, 10000)];
        }

        return DB::transaction(function () use ($attempt, $question, $student, $answer): StudentExamAnswer {
            // قفل المحاولة: إجابة بتوصل في نفس لحظة التسليم مابتتكتبش بعده
            $locked = OnlinePassedExam::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
            throw_if($locked->finished, ExamsException::because('attempt_finished'));

            return StudentExamAnswer::query()->updateOrCreate(
                ['online_passed_exam_id' => $attempt->getKey(), 'question_id' => $question->getKey()],
                [
                    'student_id' => $student->getKey(),
                    'answer' => $answer,
                    'max_question_mark' => $question->mark,
                    'grading_status' => $question->type === QuestionType::Essay ? GradingStatus::Pending : GradingStatus::Auto,
                ],
            );
        });
    }
}
