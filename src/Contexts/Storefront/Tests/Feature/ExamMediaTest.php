<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Exams\Application\Actions\SaveAnswerImagesAction;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages\ViewOnlinePassedExam;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\RelationManagers\AnswersRelationManager;
use Src\Contexts\Storefront\Presentation\Livewire\ExamRunner;
use Src\Contexts\Storefront\Presentation\Support\ExamMediaLinks;
use Src\Support\Application\Contracts\DiskResolver;

beforeEach(function (): void {
    inTenant();

    foreach (['image', 'choice_image', 'explanation_image', 'answer_images'] as $collection) {
        Storage::fake(app(DiskResolver::class)->for($collection));
    }
});

function sfImage(string $name = 'photo.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($name, 40, 40);
}

/** امتحان فيه سؤال اختيار بصورة + صورة في الاختيار الأول + صورة تفسير */
function sfIllustratedExam(array $exam = []): array
{
    $course = sfCourse();
    $item = sfExamItem($course, ['show_correct_answers_after_finish' => true, 'show_explanation' => true, ...$exam]);
    $question = $item->exam->questions()->with('choices')->firstOrFail();
    $question->update(['explanation_text' => 'Because']);
    $question->attachMedia(sfImage('q.jpg'), 'image');
    $question->attachMedia(sfImage('why.jpg'), 'explanation_image');
    $question->choices->first()->attachMedia(sfImage('c.jpg'), 'choice_image');

    return [$course, $item, $question->fresh(['media', 'choices.media'])];
}

it('صورة السؤال والاختيار بتظهر للطالب في المحاولة برابط موقّع مربوط بالمحاولة', function (): void {
    [$course, $item, $question] = sfIllustratedExam();
    $student = sfLogin();
    sfEnroll($student, $course);
    $attempt = app(StartAttemptAction::class)->handle($student, $item->exam, $item->getKey());

    $html = Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])->html();
    expect($html)->toContain('/attempts/'.$attempt->getKey().'/media/'.$question->getFirstMedia('image')->getKey())
        ->toContain('/media/'.$question->choices->first()->getFirstMedia('choice_image')->getKey())
        ->not->toContain('/media/'.$question->getFirstMedia('explanation_image')->getKey().'?');

    $this->get(ExamMediaLinks::url($attempt->getKey(), $question->getFirstMedia('image')))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('صورة التفسير مقفولة أثناء الامتحان ومفتوحة في النتيجة', function (): void {
    [$course, $item, $question] = sfIllustratedExam();
    $student = sfLogin();
    sfEnroll($student, $course);
    $attempt = app(StartAttemptAction::class)->handle($student, $item->exam, $item->getKey());
    $explanation = $question->getFirstMedia('explanation_image');

    $this->get(ExamMediaLinks::url($attempt->getKey(), $explanation))->assertNotFound();

    app(SubmitAttemptAction::class)->handle($attempt);

    $this->get(route('site.attempts.result', $attempt->getKey()))
        ->assertOk()
        ->assertSee('/media/'.$explanation->getKey(), false);
    $this->get(ExamMediaLinks::url($attempt->getKey(), $explanation))->assertOk();
});

it('رابط صورة: من غير توقيع 403، ومحاولة طالب تاني 404، وصورة من امتحان تاني 404', function (): void {
    [$course, $item, $question] = sfIllustratedExam();
    $owner = sfStudent();
    sfEnroll($owner, $course);
    $attempt = app(StartAttemptAction::class)->handle($owner, $item->exam, $item->getKey());
    $media = $question->getFirstMedia('image');

    [, $otherItem, $otherQuestion] = sfIllustratedExam();
    $this->actingAs($owner, 'student');
    auth()->shouldUse('web');

    $this->get(route('site.attempts.media', [$attempt->getKey(), $media->getKey()]))->assertForbidden();
    $this->get(ExamMediaLinks::url($attempt->getKey(), $otherQuestion->getFirstMedia('image')))->assertNotFound();

    sfLogin(); // طالب تاني
    $this->get(ExamMediaLinks::url($attempt->getKey(), $media))->assertNotFound();
});

it('الطالب بيرفع لحد ٣ صور لإجابة المقالي لو السؤال بيسمح، والمصحح بيشوفها', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, essay: 1);
    $essay = $item->exam->questions()->where('type', 'essay')->firstOrFail();
    $essay->essay()->create(['allow_images' => true]);
    $student = sfLogin();
    sfEnroll($student, $course);
    $attempt = app(StartAttemptAction::class)->handle($student, $item->exam, $item->getKey());
    $index = array_search($essay->getKey(), $attempt->question_order, true);

    Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])
        ->call('goTo', $index)
        ->assertSee(__('storefront::storefront.exams.add_images'))
        ->set("uploads.{$essay->getKey()}", [sfImage('a.jpg'), sfImage('b.png')])
        ->assertHasNoErrors();

    $answer = StudentExamAnswer::query()->where('online_passed_exam_id', $attempt->getKey())->firstOrFail();
    expect($answer->getMedia('answer_images'))->toHaveCount(2)
        ->and($answer->getFirstMedia('answer_images')->disk)->toBe(app(DiskResolver::class)->for('answer_images'));

    // الحد ٣: صورتين كمان = مرفوض
    expect(fn () => app(SaveAnswerImagesAction::class)->handle($student, $attempt, $essay->getKey(), [sfImage(), sfImage()]))
        ->toThrow(ExamsException::class, __('exams::exams.errors.too_many_images', ['max' => 3]));

    // صور من غير نص = الإجابة مستنية تصحيح مش صفر
    app(SubmitAttemptAction::class)->handle($attempt);
    expect($answer->fresh()->grading_status)->toBe(GradingStatus::Pending);

    $this->get(route('site.attempts.result', $attempt->getKey()))
        ->assertOk()
        ->assertSee('/media/'.$answer->getFirstMedia('answer_images')->getKey(), false);
});

it('السؤال اللي مش بيسمح بصور بيرفض الرفع، والمحاولة المسلّمة مابتقبلش صور', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, essay: 1);
    $essay = $item->exam->questions()->where('type', 'essay')->firstOrFail();
    $mcq = $item->exam->questions()->where('type', 'multiple_choice')->firstOrFail();
    $student = sfStudent();
    sfEnroll($student, $course);
    $attempt = app(StartAttemptAction::class)->handle($student, $item->exam, $item->getKey());

    expect(fn () => app(SaveAnswerImagesAction::class)->handle($student, $attempt, $essay->getKey(), [sfImage()]))
        ->toThrow(ExamsException::class, __('exams::exams.errors.images_not_allowed'))
        ->and(fn () => app(SaveAnswerImagesAction::class)->handle($student, $attempt, $mcq->getKey(), [sfImage()]))
        ->toThrow(ExamsException::class, __('exams::exams.errors.images_not_allowed'));

    $essay->essay()->create(['allow_images' => true]);
    app(SubmitAttemptAction::class)->handle($attempt);

    expect(fn () => app(SaveAnswerImagesAction::class)->handle($student, $attempt->fresh(), $essay->getKey(), [sfImage()]))
        ->toThrow(ExamsException::class, __('exams::exams.errors.attempt_finished'));
});

it('الطالب يقدر يشيل صورة رفعها قبل التسليم', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, essay: 1);
    $essay = $item->exam->questions()->where('type', 'essay')->firstOrFail();
    $essay->essay()->create(['allow_images' => true]);
    $student = sfLogin();
    sfEnroll($student, $course);
    $attempt = app(StartAttemptAction::class)->handle($student, $item->exam, $item->getKey());
    $answer = app(SaveAnswerImagesAction::class)->handle($student, $attempt, $essay->getKey(), [sfImage()]);

    Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])
        ->call('removeImage', $essay->getKey(), $answer->getFirstMedia('answer_images')->getKey());

    expect($answer->fresh()->getMedia('answer_images'))->toHaveCount(0);
});

it('«الإجابات بعد تاريخ»: قبل التاريخ الدرجة بس، وبعده الإجابات الصح', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, ['show_correct_answers_after_finish' => true, 'show_answers_at' => now()->addDay()]);
    $student = sfLogin();
    sfEnroll($student, $course);
    $attempt = app(StartAttemptAction::class)->handle($student, $item->exam, $item->getKey());
    app(SubmitAttemptAction::class)->handle($attempt);

    $this->get(route('site.attempts.result', $attempt->getKey()))
        ->assertOk()
        ->assertDontSee(__('storefront::storefront.exams.correct_answer'))
        // رسالة «الإجابات هتظهر يوم …» (الجزء الثابت منها قبل التاريخ)
        ->assertSeeText(str(__('storefront::storefront.exams.answers_on', ['date' => '§']))->before('§')->trim()->toString());

    $this->travel(2)->days();

    $this->get(route('site.attempts.result', $attempt->getKey()))
        ->assertOk()
        ->assertSee(__('storefront::storefront.exams.correct_answer'));
});

it('صورة السؤال مابتتقدمش برابط دائم حتى لو مجموعتها عامة', function (): void {
    [$course, $item, $question] = sfIllustratedExam();
    $student = sfLogin();
    sfEnroll($student, $course);
    $attempt = app(StartAttemptAction::class)->handle($student, $item->exam, $item->getKey());

    $html = Livewire::test(ExamRunner::class, ['attemptId' => $attempt->getKey()])->html();

    expect($html)->not->toContain($question->getFirstMedia('image')->getUrl())
        ->and(OnlinePassedExam::query()->count())->toBe(1)
        ->and(Question::query()->count())->toBe(2);
});

it('المصحح بيشوف صور إجابة المقالي في الإجابات وفي نافذة التصحيح', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course, essay: 1);
    $essay = $item->exam->questions()->where('type', 'essay')->firstOrFail();
    $essay->essay()->create(['allow_images' => true]);
    $student = sfStudent();
    sfEnroll($student, $course);
    $attempt = app(StartAttemptAction::class)->handle($student, $item->exam, $item->getKey());
    $answer = app(SaveAnswerImagesAction::class)->handle($student, $attempt, $essay->getKey(), [sfImage()]);
    app(SubmitAttemptAction::class)->handle($attempt);
    $media = $answer->getFirstMedia('answer_images');

    actingAsRole('assistant');

    Livewire::test(AnswersRelationManager::class, [
        'ownerRecord' => $attempt->fresh(),
        'pageClass' => ViewOnlinePassedExam::class,
    ])
        ->loadTable()
        ->assertSee('/media/'.$media->getKey().'/private', false)
        ->mountTableAction('grade', $answer->fresh())
        ->assertSee('/media/'.$media->getKey().'/private', false);
});
