<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Presentation\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Src\Contexts\Exams\Application\Actions\SaveAnswerAction;
use Src\Contexts\Exams\Application\Actions\SaveAnswerImagesAction;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Enums\QuestionType;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\Choice;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Storefront\Presentation\Livewire\Concerns\ResolvesStorefrontTenant;
use Src\Contexts\Storefront\Presentation\Support\ExamMediaLinks;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * المحاولة: سؤال واحد في المرة + دواير التنقل، وكل إجابة بتتحفظ فورًا
 * بـ `SaveAnswerAction` (النت لو قطع مفيش حاجة بتضيع). العداد من
 * `deadline_at` على السيرفر، وبيسلّم تلقائي عند الصفر.
 *
 * ⚠️ مفيش ولا `is_correct` في الحالة العامة للمكوّن — الـ snapshot بيوصل
 * للمتصفح. الاختيارات بتتبعت للـ view من `render()` بس.
 */
final class ExamRunner extends Component
{
    use ResolvesStorefrontTenant;
    use WithFileUploads;

    #[Locked]
    public int $attemptId;

    public int $current = 0;

    /** @var array<int|string, int|null> رقم السؤال ← رقم الاختيار */
    public array $choices = [];

    /** @var array<int|string, string> رقم السؤال ← إجابة المقالي */
    public array $texts = [];

    /** @var list<int> أسئلة متعلّم عليها للمراجعة */
    public array $flagged = [];

    /** @var array<int|string, mixed> رقم السؤال ← صور لسه مترفعة (بتتحفظ وتتفضّى على طول) */
    public array $uploads = [];

    public function mount(int $attemptId): void
    {
        $this->attemptId = $attemptId;
        $attempt = $this->attempt();

        foreach (StudentExamAnswer::query()->where('online_passed_exam_id', $attempt->getKey())->get() as $answer) {
            $qid = (int) $answer->question_id;

            if (array_key_exists('choice_id', (array) $answer->answer)) {
                $this->choices[$qid] = $answer->answer['choice_id'] !== null ? (int) $answer->answer['choice_id'] : null;
            }

            if (array_key_exists('text', (array) $answer->answer)) {
                $this->texts[$qid] = (string) $answer->answer['text'];
            }
        }
    }

    public function select(int $questionId, int $choiceId): void
    {
        if ($this->save($questionId, $choiceId, null)) {
            $this->choices[$questionId] = $choiceId;
        }
    }

    public function updatedTexts(mixed $value, string $key): void
    {
        $this->save((int) $key, null, is_string($value) ? $value : '');
    }

    /** الطالب اختار صور لإجابة المقالي — بتتحفظ فورًا زي النص */
    public function updatedUploads(mixed $value, string $key): void
    {
        $questionId = (int) explode('.', $key)[0];
        $files = array_values(array_filter(
            (array) ($this->uploads[$questionId] ?? []),
            fn (mixed $file): bool => $file instanceof TemporaryUploadedFile,
        ));
        unset($this->uploads[$questionId]);

        if ($files === []) {
            return;
        }

        $this->guarded(fn () => app(SaveAnswerImagesAction::class)->handle($this->student(), $this->attempt(), $questionId, $files));
    }

    public function removeImage(int $questionId, int $mediaId): void
    {
        $this->guarded(fn () => app(SaveAnswerImagesAction::class)->remove($this->student(), $this->attempt(), $questionId, $mediaId));
    }

    public function goTo(int $index): void
    {
        $count = count((array) $this->attempt()->question_order);
        $this->current = max(0, min($index, $count - 1));
    }

    public function toggleFlag(int $questionId): void
    {
        $this->flagged = in_array($questionId, $this->flagged, true)
            ? array_values(array_diff($this->flagged, [$questionId]))
            : [...$this->flagged, $questionId];
    }

    public function submit(): void
    {
        app(SubmitAttemptAction::class)->handle($this->attempt());

        $this->redirectRoute('site.attempts.result', $this->attemptId);
    }

    public function render(): View
    {
        $attempt = $this->attempt();
        $ids = array_map('intval', (array) $attempt->question_order);
        $models = Question::query()->with(['choices.media', 'media', 'essay'])->whereIn('id', $ids)->get()->keyBy('id');
        $answerModels = StudentExamAnswer::query()->with('media')->where('online_passed_exam_id', $attempt->getKey())->get()->keyBy('question_id');
        $exam = $attempt->exam;

        $questions = [];

        foreach ($ids as $id) {
            $question = $models->get($id);

            if (! $question instanceof Question) {
                continue;
            }

            $choices = $question->choices
                ->map(fn (Choice $c): array => [
                    'id' => (int) $c->getKey(),
                    'text' => (string) $c->text,
                    'image' => ExamMediaLinks::first($this->attemptId, $c, 'choice_image'),
                ])
                ->values()
                ->all();

            if ($exam?->random_choices) {
                // ترتيب ثابت لنفس المحاولة — مايتغيّرش مع كل ريفرش
                mt_srand($this->attemptId * 7919 + $id);
                shuffle($choices);
                mt_srand();
            }

            $questions[] = [
                'id' => $id,
                'type' => $question->type,
                'text' => (string) $question->text,
                'image' => ExamMediaLinks::first($this->attemptId, $question, 'image'),
                'mark' => (float) $question->mark,
                'choices' => $choices,
                'allow_images' => $question->type === QuestionType::Essay && $question->essay?->allow_images === true,
                'images' => ExamMediaLinks::all($this->attemptId, $answerModels->get($id), 'answer_images'),
            ];
        }

        $answered = 0;

        foreach ($questions as $q) {
            $answered += $q['type'] === QuestionType::MultipleChoice
                ? (($this->choices[$q['id']] ?? null) !== null ? 1 : 0)
                : (trim((string) ($this->texts[$q['id']] ?? '')) !== '' || $q['images'] !== [] ? 1 : 0);
        }

        $this->current = max(0, min($this->current, count($questions) - 1));

        return view('storefront::livewire.exam-runner', [
            'exam' => $exam,
            'questions' => $questions,
            'question' => $questions[$this->current] ?? null,
            'answered' => $answered,
            'maxImages' => (int) config('exams.answer_images.max', 3),
            'remaining' => $attempt->deadline_at !== null ? max(0, (int) now()->diffInSeconds($attempt->deadline_at, false)) : null,
        ]);
    }

    private function save(int $questionId, ?int $choiceId, ?string $text): bool
    {
        return $this->guarded(fn () => app(SaveAnswerAction::class)->handle($this->student(), $this->attempt(), $questionId, $choiceId, $text));
    }

    /** أي كتابة في المحاولة: الوقت خلص = تسليم، غير كده رسالة للطالب */
    private function guarded(callable $write): bool
    {
        try {
            $write();
        } catch (ExamsException $e) {
            $attempt = $this->attempt();

            if ($attempt->finished || $attempt->isExpired()) {
                $this->submit();

                return false;
            }

            $this->dispatch('storefront-toast', type: 'error', message: $e->getMessage());

            return false;
        }

        return true;
    }

    private function attempt(): OnlinePassedExam
    {
        $attempt = OnlinePassedExam::query()
            ->with('exam')
            ->whereKey($this->attemptId)
            ->where('student_id', $this->student()->getKey())
            ->first();

        abort_if($attempt === null, 404);

        return $attempt;
    }

    private function student(): Student
    {
        $student = Auth::guard('student')->user();
        abort_unless($student instanceof Student, 403);

        return $student;
    }
}
