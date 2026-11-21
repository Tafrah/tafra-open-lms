<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

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
    /** سماحية بسيطة لفرق التوقيت بين الموبايل والسيرفر */
    private const GRACE_SECONDS = 20;

    public function handle(Student $student, OnlinePassedExam $attempt, int $questionId, ?int $choiceId = null, ?string $text = null): StudentExamAnswer
    {
        throw_unless((int) $attempt->student_id === (int) $student->getKey(), ExamsException::because('attempt_not_yours'));
        throw_if($attempt->finished, ExamsException::because('attempt_finished'));
        throw_if(
            $attempt->deadline_at !== null && $attempt->deadline_at->copy()->addSeconds(self::GRACE_SECONDS)->isPast(),
            ExamsException::because('time_up'),
        );
        throw_unless(in_array($questionId, (array) $attempt->question_order, true), ExamsException::because('question_not_in_exam'));

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

        return StudentExamAnswer::query()->updateOrCreate(
            ['online_passed_exam_id' => $attempt->getKey(), 'question_id' => $question->getKey()],
            [
                'student_id' => $student->getKey(),
                'answer' => $answer,
                'max_question_mark' => $question->mark,
                'grading_status' => $question->type === QuestionType::Essay ? GradingStatus::Pending : GradingStatus::Auto,
            ],
        );
    }
}
