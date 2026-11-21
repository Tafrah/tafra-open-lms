<?php

declare(strict_types=1);

use Src\Contexts\Academy\Application\Actions\RecordItemViewAction;
use Src\Contexts\Academy\Application\Queries\ItemAccess;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Billing\Application\Actions\EnrollStudentAction;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Students\Domain\Models\Student;

beforeEach(fn () => inTenant());

it('الدرس مقفول لغير المشترك ومفتوح للمشترك', function (): void {
    $item = BootcampSectionItem::factory()->create();
    $student = Student::factory()->create();
    $access = app(ItemAccess::class);

    expect($access->denial(null, $item))->toBe('login_required')
        ->and($access->denial($student, $item))->toBe('not_enrolled');

    app(EnrollStudentAction::class)->handle($student, $item->section->bootcamp, EnrollmentSource::Admin);

    expect(app(ItemAccess::class)->allows($student, $item->fresh()))->toBeTrue();
});

it('المعاينة المجانية مفتوحة حتى للزائر', function (): void {
    $item = BootcampSectionItem::factory()->create(['is_free_preview' => true]);

    expect(app(ItemAccess::class)->allows(null, $item))->toBeTrue();
});

it('الكورس أو العنصر غير المنشور مقفول حتى للمشترك', function (): void {
    $item = BootcampSectionItem::factory()->create(['publish_status' => PublishStatus::Draft]);
    $student = Student::factory()->create();
    app(EnrollStudentAction::class)->handle($student, $item->section->bootcamp, EnrollmentSource::Admin);

    expect(app(ItemAccess::class)->denial($student, $item))->toBe('item_unavailable');
});

it('الامتحان القبلي لازم يتنجح فيه', function (): void {
    $exam = OnlineExam::factory()->create(['min_percentage' => 50]);
    $item = BootcampSectionItem::factory()->create(['prerequisite_exam_id' => $exam->getKey()]);
    $student = Student::factory()->create();
    app(EnrollStudentAction::class)->handle($student, $item->section->bootcamp, EnrollmentSource::Admin);

    expect(app(ItemAccess::class)->denial($student, $item))->toBe('prerequisite_exam');

    OnlinePassedExam::factory()->create([
        'student_id' => $student->getKey(), 'online_exam_id' => $exam->getKey(),
        'finished' => true, 'student_percentage' => 80,
    ]);

    expect(app(ItemAccess::class)->allows($student, $item))->toBeTrue();
});

it('عدد المشاهدات المسموح بيقفل الدرس لما يخلص', function (): void {
    $item = BootcampSectionItem::factory()->create(['allowed_views_number' => 2]);
    $student = Student::factory()->create();
    app(EnrollStudentAction::class)->handle($student, $item->section->bootcamp, EnrollmentSource::Admin);

    app(RecordItemViewAction::class)->handle($student, $item);
    app(RecordItemViewAction::class)->handle($student, $item); // ريفرش — مايتحسبش
    expect(app(ItemAccess::class)->allows($student, $item))->toBeTrue();

    $this->travel(2)->hours();
    app(RecordItemViewAction::class)->handle($student, $item);

    expect(app(ItemAccess::class)->denial($student, $item))->toBe('views_exhausted');
});
