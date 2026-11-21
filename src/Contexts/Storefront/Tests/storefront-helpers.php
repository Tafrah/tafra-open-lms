<?php

declare(strict_types=1);

use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Billing\Application\Actions\EnrollStudentAction;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Students\Domain\Models\Student;

/*
| مساعدات اختبارات الموقع العام — أسماؤها بـ `sf` عشان ماتتصادمش مع
| دوال Pest العامة في سياقات تانية.
*/

/** كورس منشور بجزء واحد فيه درس يوتيوب (وأي عناصر زيادة). */
function sfCourse(array $attributes = []): Bootcamp
{
    $course = Bootcamp::factory()->create($attributes);
    $section = BootcampSection::factory()->create(['bootcamp_id' => $course->getKey(), 'name' => 'Section A']);

    BootcampSectionItem::factory()->create([
        'bootcamp_section_id' => $section->getKey(),
        'name' => 'Intro video',
        'order' => 1,
        'online_lesson_id' => OnlineLesson::factory()->create(['external_url' => 'https://youtu.be/dQw4w9WgXcQ'])->getKey(),
    ]);

    return $course->fresh();
}

function sfSection(Bootcamp $course): BootcampSection
{
    return $course->sections()->firstOrFail();
}

function sfFirstItem(Bootcamp $course): BootcampSectionItem
{
    return sfSection($course)->items()->firstOrFail();
}

function sfLessonItem(Bootcamp $course, array $lesson, array $item = []): BootcampSectionItem
{
    return BootcampSectionItem::factory()->create([
        'bootcamp_section_id' => sfSection($course)->getKey(),
        'name' => 'Lesson '.uniqid(),
        'order' => 10,
        'online_lesson_id' => OnlineLesson::factory()->create($lesson)->getKey(),
        ...$item,
    ]);
}

/** امتحان بسؤالين اختيار من متعدد (الاختيار الأول صح) + مقالي اختياري، كعنصر في الكورس. */
function sfExamItem(Bootcamp $course, array $exam = [], int $essay = 0): BootcampSectionItem
{
    $model = OnlineExam::factory()->create($exam);

    for ($i = 1; $i <= 2; $i++) {
        Question::factory()->withChoices()->create(['online_exam_id' => $model->getKey(), 'order' => $i, 'mark' => 2, 'text' => "<p>MCQ {$i}</p>"]);
    }

    for ($i = 1; $i <= $essay; $i++) {
        Question::factory()->essay()->create(['online_exam_id' => $model->getKey(), 'order' => 10 + $i, 'text' => '<p>Essay</p>']);
    }

    return BootcampSectionItem::factory()->create([
        'bootcamp_section_id' => sfSection($course)->getKey(),
        'name' => 'Exam item',
        'order' => 20,
        'type' => SectionItemType::Exam,
        'online_lesson_id' => null,
        'online_exam_id' => $model->getKey(),
    ]);
}

function sfStudent(array $attributes = []): Student
{
    // fresh(): الموديل من الفاكتوري ناقصه الأعمدة الافتراضية (balance…) والموديلات strict
    return Student::factory()->create($attributes)->fresh();
}

function sfLogin(?Student $student = null): Student
{
    $student ??= sfStudent();
    test()->actingAs($student, 'student');

    // `actingAs` بيخلّي `student` الحارس الافتراضي — في الإنتاج الافتراضي `web`
    // والطالب بيتسأل عنه بـ `auth('student')` صراحةً، فنرجّع نفس الوضع
    auth()->shouldUse('web');

    return $student;
}

function sfEnroll(Student $student, Bootcamp $course): void
{
    app(EnrollStudentAction::class)->handle($student, $course, EnrollmentSource::Admin);
}

/** @param array<string, mixed> $values */
function sfSiteSettings(array $values): void
{
    foreach ($values as $key => $value) {
        app(TenantSettings::class)->set('site', $key, $value);
    }
}

function sfPdfLesson(): array
{
    return ['type' => LessonType::Pdf, 'external_url' => null];
}
