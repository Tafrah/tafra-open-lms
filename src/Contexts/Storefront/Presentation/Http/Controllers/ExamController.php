<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Src\Contexts\Academy\Application\Queries\ItemAccess;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Storefront\Presentation\Http\Controllers\Concerns\ResolvesStudent;
use Src\Support\Domain\Exceptions\DomainException;

/**
 * الامتحان: صفحة البداية (معلومات + ابدأ) ← المحاولة (Livewire) ← النتيجة.
 * المحاولة بتتجاب بـ `student_id` الطالب الداخل — محاولة غيره = 404.
 */
final class ExamController
{
    use ResolvesStudent;

    public function __construct(private readonly ItemAccess $access) {}

    public function start(int $item): Response
    {
        $student = $this->student();
        [$sectionItem, $exam] = $this->examItem($item);
        $denial = $this->access->denial($student, $sectionItem);

        $attempts = OnlinePassedExam::query()
            ->where('student_id', $student->getKey())
            ->where('online_exam_id', $exam->getKey())
            ->latest('id')
            ->get();

        $open = $attempts->first(fn (OnlinePassedExam $a): bool => ! $a->finished && ! $a->isExpired());

        return response()->view('storefront::pages.exams.start', [
            'item' => $sectionItem,
            'course' => $sectionItem->section?->bootcamp,
            'exam' => $exam,
            'denial' => $denial,
            'questionsCount' => $exam->questions()->count(),
            'attempts' => $attempts,
            'open' => $open,
            'chancesLeft' => $exam->allowed_chances === null ? null : max(0, $exam->allowed_chances - $attempts->count()),
        ], $denial === null ? 200 : 403);
    }

    public function begin(int $item, StartAttemptAction $start): RedirectResponse
    {
        $student = $this->student();
        [$sectionItem, $exam] = $this->examItem($item);

        if (($denial = $this->access->denial($student, $sectionItem)) !== null) {
            return redirect()->route('site.exams.start', $item)->with('error', __('academy::academy.errors.'.$denial));
        }

        try {
            $attempt = $start->handle($student, $exam, (int) $sectionItem->getKey());
        } catch (DomainException $e) {
            return redirect()->route('site.exams.start', $item)->with('error', $e->getMessage());
        }

        return redirect()->route('site.attempts.show', $attempt->getKey());
    }

    public function attempt(int $attempt, SubmitAttemptAction $submit): View|RedirectResponse
    {
        $record = $this->ownAttempt($attempt);

        if (! $record->finished && $record->isExpired()) {
            $submit->handle($record);
            $record->refresh();
        }

        if ($record->finished) {
            return redirect()->route('site.attempts.result', $record->getKey());
        }

        return view('storefront::pages.exams.attempt', ['attempt' => $record, 'exam' => $record->exam]);
    }

    public function result(int $attempt): View|RedirectResponse
    {
        $record = $this->ownAttempt($attempt);

        if (! $record->finished) {
            return redirect()->route('site.attempts.show', $record->getKey());
        }

        $exam = $record->exam;
        $ids = array_map('intval', (array) $record->question_order);
        $questions = Question::query()->with(['choices', 'media'])->whereIn('id', $ids)->get()->keyBy('id');
        $answers = StudentExamAnswer::query()->where('online_passed_exam_id', $record->getKey())->get()->keyBy('question_id');

        $ordered = collect($ids)->map(fn (int $id): ?Question => $questions->get($id))->filter()->values();

        $correct = 0;
        $wrong = 0;
        $unanswered = 0;

        foreach ($ordered as $question) {
            /** @var StudentExamAnswer|null $answer */
            $answer = $answers->get($question->getKey());
            $given = $question->type === QuestionType::MultipleChoice
                ? ($answer?->answer['choice_id'] ?? null)
                : trim((string) ($answer?->answer['text'] ?? ''));

            if ($given === null || $given === '') {
                $unanswered++;
            } elseif ($answer?->grading_status === GradingStatus::Pending) {
                continue;
            } elseif ($answer?->is_correct === true || ($question->type === QuestionType::Essay && (float) $answer?->student_awarded_mark > 0)) {
                $correct++;
            } else {
                $wrong++;
            }
        }

        return view('storefront::pages.exams.result', [
            'attempt' => $record,
            'exam' => $exam,
            'questions' => $ordered,
            'answers' => $answers,
            'counts' => ['correct' => $correct, 'wrong' => $wrong, 'unanswered' => $unanswered],
            'pending' => $record->grading_status === GradingStatus::Pending,
            'showAnswers' => (bool) $exam?->show_correct_answers_after_finish,
            'showExplanation' => (bool) $exam?->show_explanation,
            'item' => $record->bootcamp_section_item_id !== null
                ? BootcampSectionItem::query()->with('section.bootcamp')->find($record->bootcamp_section_item_id)
                : null,
        ]);
    }

    /** @return array{0: BootcampSectionItem, 1: OnlineExam} */
    private function examItem(int $item): array
    {
        $sectionItem = BootcampSectionItem::query()->with(['section.bootcamp', 'exam'])->find($item);

        abort_if($sectionItem === null || $sectionItem->type !== SectionItemType::Exam || $sectionItem->exam === null, 404);

        return [$sectionItem, $sectionItem->exam];
    }

    private function ownAttempt(int $attempt): OnlinePassedExam
    {
        $record = OnlinePassedExam::query()
            ->with('exam')
            ->whereKey($attempt)
            ->where('student_id', $this->student()->getKey())
            ->first();

        abort_if($record === null || $record->exam === null, 404);

        return $record;
    }
}
