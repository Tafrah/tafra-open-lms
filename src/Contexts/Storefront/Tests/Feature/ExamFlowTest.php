<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Livewire\Livewire;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Storefront\Presentation\Livewire\ExamRunner;

beforeEach(fn () => inTenant());

it('صفحة بداية الامتحان بتعرض عدد الأسئلة والمدة والمحاولات', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, ['allowed_chances' => 3, 'duration_in_seconds' => 600, 'min_percentage' => 60]);
    sfEnroll(sfLogin(), $course);

    $this->get(route('site.exams.start', $item->getKey()))
        ->assertOk()
        ->assertSee(__('storefront::storefront.exams.start'))
        ->assertSee('60%');
});

it('غير المشترك مايقدرش يبدأ الامتحان', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course);
    sfLogin();

    $this->get(route('site.exams.start', $item->getKey()))->assertForbidden();
    $this->post(route('site.exams.begin', $item->getKey()))->assertRedirect(route('site.exams.start', $item->getKey()));

    expect(OnlinePassedExam::query()->count())->toBe(0);
});

it('ابدأ ← جاوب (كل إجابة بتتحفظ) ← سلّم ← النتيجة', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, ['show_correct_answers_after_finish' => true, 'show_explanation' => true]);
    $student = sfLogin();
    sfEnroll($student, $course);

    $this->post(route('site.exams.begin', $item->getKey()))->assertRedirect();
    $attempt = OnlinePassedExam::query()->where('student_id', $student->getKey())->firstOrFail();
    $this->get(route('site.attempts.show', $attempt->getKey()))->assertOk();

    $questions = $item->exam->questions()->with('choices')->get();
    [$q1, $q2] = $questions->all();

    $runner = Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])
        ->assertSee('MCQ 1')
        ->assertDontSee('is_correct')
        ->call('select', $q1->getKey(), $q1->choices->firstWhere('is_correct', true)->getKey());

    expect(StudentExamAnswer::query()->where('online_passed_exam_id', $attempt->getKey())->count())->toBe(1);

    $runner->call('goTo', 1)
        ->call('select', $q2->getKey(), $q2->choices->firstWhere('is_correct', false)->getKey())
        ->call('submit')
        ->assertRedirect(route('site.attempts.result', $attempt->getKey()));

    $attempt->refresh();
    expect($attempt->finished)->toBeTrue()
        ->and((float) $attempt->student_percentage)->toBe(50.0);

    $this->get(route('site.attempts.result', $attempt->getKey()))
        ->assertOk()
        ->assertSee('50%')
        ->assertSee(__('storefront::storefront.exams.correct_answer'))
        ->assertSee(__('storefront::storefront.exams.your_choice'));

    // بعد التسليم صفحة المحاولة بتودّي للنتيجة
    $this->get(route('site.attempts.show', $attempt->getKey()))->assertRedirect(route('site.attempts.result', $attempt->getKey()));
});

it('المقالي بيخلّي النتيجة «مستني تصحيح»', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, essay: 1);
    $student = sfLogin();
    sfEnroll($student, $course);

    $this->post(route('site.exams.begin', $item->getKey()));
    $attempt = OnlinePassedExam::query()->firstOrFail();
    $essay = $item->exam->questions()->where('type', 'essay')->firstOrFail();

    Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])
        ->set("texts.{$essay->getKey()}", 'My long essay answer')
        ->call('submit');

    expect($attempt->fresh()->grading_status)->toBe(GradingStatus::Pending);

    $this->get(route('site.attempts.result', $attempt->getKey()))->assertOk()->assertSee(__('storefront::storefront.exams.awaiting_essay'));
});

it('الإجابات الصح مابتظهرش لو الامتحان مش مسموح فيه', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, ['show_correct_answers_after_finish' => false]);
    sfEnroll(sfLogin(), $course);

    $this->post(route('site.exams.begin', $item->getKey()));
    $attempt = OnlinePassedExam::query()->firstOrFail();
    Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])->call('submit');

    $this->get(route('site.attempts.result', $attempt->getKey()))
        ->assertOk()
        ->assertDontSee(__('storefront::storefront.exams.correct_answer'));
});

it('الوقت لما يخلص صفحة المحاولة بتسلّمها لوحدها', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, ['duration_in_seconds' => 60]);
    sfEnroll(sfLogin(), $course);

    $this->post(route('site.exams.begin', $item->getKey()));
    $attempt = OnlinePassedExam::query()->firstOrFail();

    $this->travel(5)->minutes();

    $this->get(route('site.attempts.show', $attempt->getKey()))->assertRedirect(route('site.attempts.result', $attempt->getKey()));
    expect($attempt->fresh()->finished)->toBeTrue();
});

it('محاولة طالب تاني = 404 (الصفحة والنتيجة والمكوّن)', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course);
    $owner = sfStudent();
    sfEnroll($owner, $course);
    $attempt = app(StartAttemptAction::class)->handle($owner, $item->exam, $item->getKey());

    sfLogin();

    $this->get(route('site.attempts.show', $attempt->getKey()))->assertNotFound();
    $this->get(route('site.attempts.result', $attempt->getKey()))->assertNotFound();

    Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])->assertStatus(404);
});

it('مايقدرش يجاوب على سؤال مش في المحاولة', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course);
    $foreign = sfExamItem(sfCourse());
    $student = sfLogin();
    sfEnroll($student, $course);

    $this->post(route('site.exams.begin', $item->getKey()));
    $attempt = OnlinePassedExam::query()->firstOrFail();
    $q = $foreign->exam->questions()->with('choices')->first();

    Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])
        ->call('select', $q->getKey(), $q->choices->first()->getKey())
        ->assertDispatched('storefront-toast');

    expect(StudentExamAnswer::query()->count())->toBe(0);
});
