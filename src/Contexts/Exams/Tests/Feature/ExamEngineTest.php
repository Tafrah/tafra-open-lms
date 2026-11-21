<?php

declare(strict_types=1);

use Src\Contexts\Exams\Application\Actions\GradeEssayAnswerAction;
use Src\Contexts\Exams\Application\Actions\SaveAnswerAction;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Students\Domain\Models\Student;

beforeEach(fn () => inTenant());

function examWith(int $mcq = 2, int $essay = 0, array $attributes = []): OnlineExam
{
    $exam = OnlineExam::factory()->create($attributes);

    // مش range(): range(1, 0) بترجع [1, 0]
    for ($i = 1; $i <= $mcq; $i++) {
        Question::factory()->withChoices()->create(['online_exam_id' => $exam->getKey(), 'order' => $i, 'mark' => 2]);
    }

    for ($i = 1; $i <= $essay; $i++) {
        Question::factory()->essay()->create(['online_exam_id' => $exam->getKey(), 'order' => 100 + $i]);
    }

    return $exam;
}

it('الاختيار من متعدد بيتصحح تلقائي والنسبة بتتحسب', function (): void {
    $exam = examWith(mcq: 2);
    $student = Student::factory()->create();
    $attempt = app(StartAttemptAction::class)->handle($student, $exam);

    [$q1, $q2] = $exam->questions()->with('choices')->get()->all();
    app(SaveAnswerAction::class)->handle($student, $attempt, $q1->getKey(), $q1->choices->firstWhere('is_correct', true)->getKey());
    app(SaveAnswerAction::class)->handle($student, $attempt, $q2->getKey(), $q2->choices->firstWhere('is_correct', false)->getKey());

    $result = app(SubmitAttemptAction::class)->handle($attempt);

    expect($result->finished)->toBeTrue()
        ->and((float) $result->student_total_mark)->toBe(2.0)
        ->and((float) $result->max_total_mark)->toBe(4.0)
        ->and((float) $result->student_percentage)->toBe(50.0)
        ->and($result->grading_status)->toBe(GradingStatus::Graded)
        ->and($result->passed())->toBeTrue();
});

it('المقالي بيخلّي المحاولة «مستنية تصحيح» لحد ما المدرس يدّي درجة', function (): void {
    $admin = userWithRole('admin');
    inTenant();
    $exam = examWith(mcq: 1, essay: 1);
    $student = Student::factory()->create();
    $attempt = app(StartAttemptAction::class)->handle($student, $exam);
    $essay = $exam->questions()->where('type', 'essay')->first();

    app(SaveAnswerAction::class)->handle($student, $attempt, $essay->getKey(), text: 'الإجابة بتاعتي');
    $result = app(SubmitAttemptAction::class)->handle($attempt);

    expect($result->grading_status)->toBe(GradingStatus::Pending)->and($result->passed())->toBeFalse();

    $answer = StudentExamAnswer::query()->where('question_id', $essay->getKey())->first();
    app(GradeEssayAnswerAction::class)->handle($answer, 4, 'كويس', $admin);

    $result->refresh();
    expect($result->grading_status)->toBe(GradingStatus::Graded)
        ->and((float) $result->student_total_mark)->toBe(4.0);
});

it('الدرجة أكبر من درجة السؤال مرفوضة', function (): void {
    $admin = userWithRole('admin');
    inTenant();
    $exam = examWith(mcq: 0, essay: 1);
    $student = Student::factory()->create();
    $attempt = app(StartAttemptAction::class)->handle($student, $exam);
    $essay = $exam->questions()->first();
    app(SaveAnswerAction::class)->handle($student, $attempt, $essay->getKey(), text: 'x');
    app(SubmitAttemptAction::class)->handle($attempt);

    $answer = StudentExamAnswer::query()->first();

    expect(fn () => app(GradeEssayAnswerAction::class)->handle($answer, 99, null, $admin))->toThrow(ExamsException::class);
});

it('عدد المحاولات بيتحترم، والمحاولة المفتوحة بتكمّل مش بتتعاد', function (): void {
    $exam = examWith(attributes: ['allowed_chances' => 1]);
    $student = Student::factory()->create();

    $first = app(StartAttemptAction::class)->handle($student, $exam);
    $again = app(StartAttemptAction::class)->handle($student, $exam);
    expect($again->getKey())->toBe($first->getKey());

    app(SubmitAttemptAction::class)->handle($first);

    expect(fn () => app(StartAttemptAction::class)->handle($student, $exam))->toThrow(ExamsException::class);
});

it('بعد ما الوقت يخلص مفيش حفظ إجابات، والمحاولة بتتقفل تلقائي', function (): void {
    $exam = examWith(attributes: ['duration_in_seconds' => 60, 'allowed_chances' => 2]);
    $student = Student::factory()->create();
    $attempt = app(StartAttemptAction::class)->handle($student, $exam);
    $question = $exam->questions()->first();

    $this->travel(5)->minutes();

    expect(fn () => app(SaveAnswerAction::class)->handle($student, $attempt, $question->getKey(), null))
        ->toThrow(ExamsException::class);

    // بداية محاولة جديدة بتسلّم القديمة المنتهية الأول
    $next = app(StartAttemptAction::class)->handle($student, $exam);
    expect($attempt->fresh()->finished)->toBeTrue()->and($next->current_student_chance)->toBe(2);
});

it('طالب مايقدرش يجاوب في محاولة طالب تاني', function (): void {
    $exam = examWith();
    $owner = Student::factory()->create();
    $attempt = app(StartAttemptAction::class)->handle($owner, $exam);

    expect(fn () => app(SaveAnswerAction::class)->handle(Student::factory()->create(), $attempt, $exam->questions()->first()->getKey(), null))
        ->toThrow(ExamsException::class);
});

it('الامتحان غير المنشور أو برا ميعاده مقفول', function (): void {
    $student = Student::factory()->create();

    expect(fn () => app(StartAttemptAction::class)->handle($student, examWith(attributes: ['published' => false])))->toThrow(ExamsException::class)
        ->and(fn () => app(StartAttemptAction::class)->handle($student, examWith(attributes: ['start_at' => now()->addDay()])))->toThrow(ExamsException::class);
});
