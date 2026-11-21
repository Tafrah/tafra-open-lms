<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Illuminate\Support\Facades\DB;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;

/**
 * تسليم المحاولة: الاختيار من متعدد بيتصحح فورًا، والمقالي بيستنى المدرس.
 * بيتنادى من الطالب («سلّم»)، أو تلقائي لما الوقت يخلص.
 */
final readonly class SubmitAttemptAction
{
    public function __construct(private RecalculateAttemptAction $recalculate) {}

    public function handle(OnlinePassedExam $attempt): OnlinePassedExam
    {
        return DB::transaction(function () use ($attempt): OnlinePassedExam {
            /** @var OnlinePassedExam $locked */
            $locked = OnlinePassedExam::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->finished) {
                return $locked;
            }

            $ids = array_map('intval', (array) $locked->question_order);
            $questions = Question::query()->with('choices')->whereIn('id', $ids)->get()->keyBy('id');
            $answers = StudentExamAnswer::query()->where('online_passed_exam_id', $locked->getKey())->get()->keyBy('question_id');

            foreach ($ids as $id) {
                $question = $questions->get($id);

                if ($question === null) {
                    continue;
                }

                /** @var StudentExamAnswer|null $answer */
                $answer = $answers->get($id);
                $max = (float) $question->mark;

                if ($question->type === QuestionType::MultipleChoice) {
                    $choiceId = $answer?->answer['choice_id'] ?? null;
                    $correct = $choiceId !== null && $question->choices->firstWhere('id', $choiceId)?->is_correct === true;

                    $this->store($locked, $question, $answer, [
                        'is_correct' => $correct,
                        'student_awarded_mark' => $correct ? $max : 0,
                        'max_question_mark' => $max,
                        'grading_status' => GradingStatus::Auto,
                    ]);

                    continue;
                }

                $text = trim((string) ($answer?->answer['text'] ?? ''));
                $this->store($locked, $question, $answer, $text === ''
                    // مقالي متسابش فاضي = صفر، مفيش داعي يستنى تصحيح
                    ? ['is_correct' => false, 'student_awarded_mark' => 0, 'max_question_mark' => $max, 'grading_status' => GradingStatus::Graded]
                    : ['max_question_mark' => $max, 'grading_status' => GradingStatus::Pending]);
            }

            $locked->forceFill(['finished' => true, 'end_at' => now()])->save();

            return $this->recalculate->handle($locked);
        });
    }

    /** @param array<string, mixed> $values */
    private function store(OnlinePassedExam $attempt, Question $question, ?StudentExamAnswer $answer, array $values): void
    {
        if ($answer !== null) {
            $answer->forceFill($values)->save();

            return;
        }

        StudentExamAnswer::query()->create([
            'online_passed_exam_id' => $attempt->getKey(),
            'student_id' => $attempt->student_id,
            'question_id' => $question->getKey(),
            'answer' => null,
            ...$values,
        ]);
    }
}
