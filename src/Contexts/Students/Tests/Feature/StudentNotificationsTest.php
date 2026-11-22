<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Billing\Application\Actions\CheckoutAction;
use Src\Contexts\Billing\Application\Actions\ReviewInvoiceAction;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Models\PaymentInvoice;
use Src\Contexts\Content\Domain\Events\AnnouncementPublished;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Infrastructure\Listeners\NotifyUsersOfAnnouncement;
use Src\Contexts\Content\Infrastructure\Notifications\AnnouncementPublishedNotification;
use Src\Contexts\Exams\Application\Actions\GradeEssayAnswerAction;
use Src\Contexts\Exams\Application\Actions\SaveAnswerAction;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Notifications\Application\Services\NotificationChannelResolver;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Infrastructure\Notifications\AttemptGradedNotification;
use Src\Contexts\Students\Infrastructure\Notifications\InvoiceReviewedNotification;

/*
| إشعارات الطالب على الموقع: الإيصال اتقبل/اترفض، والمقالي اتصحح.
| بتتخزّن في `notifications` على موديل الطالب (database بس).
*/

beforeEach(fn () => inTenant());

function stnPendingInvoice(Student $student, string $course = 'Chemistry 3'): PaymentInvoice
{
    return app(CheckoutAction::class)->handle($student, Bootcamp::factory()->create(['name' => $course]), PaymentGateway::ManualTransfer);
}

it('قبول الإيصال بيوصّل إشعار للطالب برابط الكورس', function (): void {
    $admin = userWithRole('admin');
    $student = Student::factory()->create();
    $invoice = stnPendingInvoice($student);

    app(ReviewInvoiceAction::class)->approve($invoice, $admin);

    $notification = $student->notifications()->sole();

    expect($notification->type)->toBe(InvoiceReviewedNotification::class)
        ->and($notification->read_at)->toBeNull()
        ->and($notification->data['kind'])->toBe('invoice_approved')
        ->and($notification->data['params']['course'])->toBe('Chemistry 3')
        ->and($notification->data['route'])->toBe('site.learn.course')
        ->and($notification->data['route_params'])->toBe(['slug' => $invoice->bootcamp->slug]);
});

it('رفض الإيصال بيوصّل إشعار فيه السبب', function (): void {
    $admin = userWithRole('admin');
    $student = Student::factory()->create();

    app(ReviewInvoiceAction::class)->reject(stnPendingInvoice($student), $admin, 'الصورة مش واضحة');

    $data = $student->notifications()->sole()->data;

    expect($data['kind'])->toBe('invoice_rejected')
        ->and($data['params']['reason'])->toBe('الصورة مش واضحة')
        ->and($data['route'])->toBe('site.invoices');
});

it('الإشعار للطالب صاحب الفاتورة بس', function (): void {
    $admin = userWithRole('admin');
    $owner = Student::factory()->create();
    $other = Student::factory()->create();

    app(ReviewInvoiceAction::class)->approve(stnPendingInvoice($owner), $admin);

    expect($owner->notifications()->count())->toBe(1)
        ->and($other->notifications()->count())->toBe(0)
        ->and($admin->notifications()->where('type', InvoiceReviewedNotification::class)->count())->toBe(0);
});

it('تصحيح آخر سؤال مقالي بيوصّل للطالب نتيجته النهائية', function (): void {
    $admin = userWithRole('admin');
    $exam = OnlineExam::factory()->create(['name' => 'Unit 1 quiz']);
    $essay = Question::factory()->essay()->create(['online_exam_id' => $exam->getKey(), 'order' => 1]);
    $student = Student::factory()->create();

    $attempt = app(StartAttemptAction::class)->handle($student, $exam);
    app(SaveAnswerAction::class)->handle($student, $attempt, $essay->getKey(), text: 'إجابتي');
    app(SubmitAttemptAction::class)->handle($attempt);

    expect($student->notifications()->count())->toBe(0);

    $answer = StudentExamAnswer::query()->sole();
    app(GradeEssayAnswerAction::class)->handle($answer, (float) $answer->max_question_mark, 'ممتاز', $admin);

    $notification = $student->notifications()->sole();

    expect($notification->type)->toBe(AttemptGradedNotification::class)
        ->and($notification->data['kind'])->toBe('attempt_graded')
        ->and($notification->data['params']['exam'])->toBe('Unit 1 quiz')
        ->and((float) $notification->data['params']['percentage'])->toBe(100.0)
        ->and($notification->data['route'])->toBe('site.attempts.result')
        ->and($notification->data['route_params'])->toBe(['attempt' => $attempt->getKey()]);
});

it('إشعارات الطالب قناة database بس — من غير تفضيلات مستخدمي اللوحة', function (): void {
    $student = Student::factory()->create();
    $notification = new AttemptGradedNotification(1, 'Exam', 50.0);

    expect($notification->via($student))->toBe(['database'])
        ->and(app(NotificationChannelResolver::class)->for($student, 'student_attempt_graded'))->toBe(['database']);
});

it('إعلانات المدرس لسه بتوصل لمستخدمي اللوحة زي ما هي، ومابتتبعتش كإشعار للطلبة', function (): void {
    Notification::fake();
    $tenant = currentTenant();
    $user = userWithRole('viewer');
    $student = Student::factory()->create();
    $announcement = Announcement::factory()->published()->create();

    app(NotifyUsersOfAnnouncement::class)->handle(new AnnouncementPublished((int) $announcement->getKey(), (int) $tenant->getKey()));

    Notification::assertSentTo($user, AnnouncementPublishedNotification::class);
    Notification::assertNotSentTo($student, AnnouncementPublishedNotification::class);
});
