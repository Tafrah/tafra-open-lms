<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\SectionItemView;
use Src\Support\Application\Contracts\DiskResolver;

beforeEach(fn () => inTenant());

function sfPdfItem(Bootcamp $course): BootcampSectionItem
{
    $item = sfLessonItem($course, sfPdfLesson());
    $item->lesson->addMedia(UploadedFile::fake()->createWithContent('notes.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"))
        ->toMediaCollection('documents', app(DiskResolver::class)->for('documents'));

    return $item->fresh(['lesson']);
}

it('الدرس مقفول (403) لغير المشترك ومفتوح للمشترك بمشغّل يوتيوب من غير كوكيز', function (): void {
    $course = sfCourse();
    $item = sfFirstItem($course);
    $student = sfLogin();

    $this->get(route('site.learn', [$course->slug, $item->getKey()]))
        ->assertForbidden()
        ->assertSee(__('academy::academy.errors.not_enrolled'))
        ->assertDontSee('youtube-nocookie.com/embed', escape: false);

    sfEnroll($student, $course);

    $this->get(route('site.learn', [$course->slug, $item->getKey()]))
        ->assertOk()
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', escape: false)
        ->assertDontSee('youtu.be/dQw4w9WgXcQ', escape: false)
        ->assertSee('data-tafra-credit', escape: false);

    expect(SectionItemView::query()->where('student_id', $student->getKey())->value('views_count'))->toBe(1);
});

it('المعاينة المجانية مفتوحة لأي طالب داخل', function (): void {
    $course = sfCourse();
    $item = sfLessonItem($course, [], ['is_free_preview' => true]);
    sfLogin();

    $this->get(route('site.learn', [$course->slug, $item->getKey()]))->assertOk();
});

it('عنصر من كورس تاني بنفس الرابط = 404', function (): void {
    $course = sfCourse();
    $other = sfCourse();
    sfEnroll(sfLogin(), $course);

    $this->get(route('site.learn', [$course->slug, sfFirstItem($other)->getKey()]))->assertNotFound();
});

it('«خلّصت الدرس» بيعلّم عليه وبيودّي للي بعده', function (): void {
    $course = sfCourse();
    $first = sfFirstItem($course);
    $second = sfLessonItem($course, []);
    $student = sfLogin();
    sfEnroll($student, $course);

    $this->post(route('site.learn.complete', [$course->slug, $first->getKey()]))
        ->assertRedirect(route('site.learn', [$course->slug, $second->getKey()]));

    expect(SectionItemView::query()->where('student_id', $student->getKey())->where('bootcamp_section_item_id', $first->getKey())->value('is_completed'))->toBeTrue();

    $this->get(route('site.my-courses'))->assertOk()->assertSee('50%');
});

it('الدرس النصي بيتعرض منقّى', function (): void {
    $course = sfCourse();
    $item = sfLessonItem($course, ['type' => LessonType::Text, 'external_url' => null, 'text' => '<p>Hello <strong>chem</strong></p><img src=x onerror="alert(1)">']);
    sfEnroll(sfLogin(), $course);

    $this->get(route('site.learn', [$course->slug, $item->getKey()]))
        ->assertOk()
        ->assertSee('<strong>chem</strong>', escape: false)
        ->assertDontSee('onerror', escape: false);
});

it('/learn/{slug} بيودّي لأول درس مفتوح', function (): void {
    $course = sfCourse();
    sfEnroll(sfLogin(), $course);

    $this->get(route('site.learn.course', $course->slug))->assertRedirect(route('site.learn', [$course->slug, sfFirstItem($course)->getKey()]));
});

it('ملف الـ PDF بيتعرض inline برابط موقّع للمشترك بس', function (): void {
    Storage::fake(app(DiskResolver::class)->for('documents'));
    $course = sfCourse();
    $item = sfPdfItem($course);
    $student = sfLogin();
    sfEnroll($student, $course);

    $page = $this->get(route('site.learn', [$course->slug, $item->getKey()]))->assertOk();
    expect($page->getContent())->toContain('signature=')
        ->and($page->getContent())->not->toContain('/storage/');

    $signed = URL::temporarySignedRoute('site.learn.document', now()->addMinutes(5), [$course->slug, $item->getKey()]);

    $response = $this->get($signed)->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('inline');
});

it('رابط الـ PDF من غير توقيع = 403، وبتوقيع لطالب مش مشترك = 403', function (): void {
    Storage::fake(app(DiskResolver::class)->for('documents'));
    $course = sfCourse();
    $item = sfPdfItem($course);
    sfEnroll(sfStudent(), $course);
    sfLogin(); // طالب تاني مش مشترك

    $this->get(route('site.learn.document', [$course->slug, $item->getKey()]))->assertForbidden();

    $signed = URL::temporarySignedRoute('site.learn.document', now()->addMinutes(5), [$course->slug, $item->getKey()]);
    $this->get($signed)->assertForbidden();
});

it('رابط الـ PDF الموقّع بيخلص بعد مدته', function (): void {
    Storage::fake(app(DiskResolver::class)->for('documents'));
    $course = sfCourse();
    $item = sfPdfItem($course);
    sfEnroll(sfLogin(), $course);

    $signed = URL::temporarySignedRoute('site.learn.document', now()->addMinutes(5), [$course->slug, $item->getKey()]);
    $this->travel(6)->minutes();

    $this->get($signed)->assertForbidden();
});
