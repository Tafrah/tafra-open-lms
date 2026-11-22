<?php

declare(strict_types=1);

require_once __DIR__.'/../storefront-helpers.php';

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Src\Contexts\Academy\Application\Actions\GrantExtraViewsAction;
use Src\Contexts\Academy\Application\Actions\RecordItemViewAction;
use Src\Contexts\Academy\Application\Queries\ItemAccess;
use Src\Contexts\Academy\Domain\Exceptions\AcademyException;
use Src\Contexts\Academy\Domain\Models\StudentItemViewGrant;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\EditStudent;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers\ViewGrantsRelationManager;
use Src\Support\Application\Contracts\DiskResolver;

beforeEach(fn () => inTenant());

/** الطالب شاف الدرس كل مشاهداته (المشاهدة الأخيرة من أكتر من ساعة) */
function sfUseAllViews($student, $item): void
{
    for ($i = 0; $i < (int) $item->allowed_views_number; $i++) {
        app(RecordItemViewAction::class)->handle($student, $item);
        test()->travel(RecordItemViewAction::SAME_VIEW_MINUTES + 1)->minutes();
    }
}

// ═══════════ مشاهدات زيادة لطالب واحد ═══════════

it('مشاهدات زيادة لطالب بتفتحله الدرس تاني، وزمايله لأ', function (): void {
    $course = sfCourse();
    $item = sfLessonItem($course, [], ['allowed_views_number' => 1]);
    $student = sfStudent();
    $classmate = sfStudent();
    sfEnroll($student, $course);
    sfEnroll($classmate, $course);
    sfUseAllViews($student, $item);
    sfUseAllViews($classmate, $item);

    expect(app(ItemAccess::class)->denial($student, $item->fresh()))->toBe('views_exhausted');

    $admin = actingAsRole('admin');
    app(GrantExtraViewsAction::class)->handle($student, $item, 2, $admin);

    expect(app(ItemAccess::class)->denial($student, $item->fresh()))->toBeNull()
        ->and(app(ItemAccess::class)->denial($classmate, $item->fresh()))->toBe('views_exhausted');

    // المنح بتتجمّع: ١ أساسي + ٢ + ١ = ٤
    app(GrantExtraViewsAction::class)->handle($student, $item, 1, $admin);
    expect(StudentItemViewGrant::query()->where('student_id', $student->getKey())->value('extra_views'))->toBe(3);

    test()->travel(2)->hours();
    sfUseAllViews($student, $item->fresh()->forceFill(['allowed_views_number' => 3]));
    expect(app(ItemAccess::class)->denial($student, $item->fresh()))->toBe('views_exhausted');
});

it('المشاهدات الزيادة لدرس مشاهداته مفتوحة مرفوضة، والعدد له حدود', function (): void {
    $course = sfCourse();
    $open = sfLessonItem($course, []);
    $limited = sfLessonItem($course, [], ['allowed_views_number' => 2]);
    $student = sfStudent();
    $admin = actingAsRole('admin');

    expect(fn () => app(GrantExtraViewsAction::class)->handle($student, $open, 1, $admin))->toThrow(AcademyException::class)
        ->and(fn () => app(GrantExtraViewsAction::class)->handle($student, $limited, 0, $admin))->toThrow(AcademyException::class)
        ->and(fn () => app(GrantExtraViewsAction::class)->handle($student, $limited, 101, $admin))->toThrow(AcademyException::class);
});

it('المساعد يقدر يدّي مشاهدات زيادة، والمشاهد لأ', function (): void {
    $course = sfCourse();
    $item = sfLessonItem($course, [], ['allowed_views_number' => 1]);
    $student = sfStudent();

    $viewer = actingAsRole('viewer');
    expect(fn () => app(GrantExtraViewsAction::class)->handle($student, $item, 1, $viewer))->toThrow(AuthorizationException::class);

    $assistant = actingAsRole('assistant');
    expect(app(GrantExtraViewsAction::class)->handle($student, $item, 1, $assistant)->extra_views)->toBe(1);
});

it('من صفحة الطالب: زرار «مشاهدات زيادة» بيضيف المنحة وزرار الإلغاء بيشيلها', function (): void {
    $course = sfCourse();
    $item = sfLessonItem($course, [], ['allowed_views_number' => 1]);
    $student = sfStudent();
    actingAsRole('admin');

    $rm = Livewire::test(ViewGrantsRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->assertOk()
        ->callTableAction('grantViews', data: ['item_id' => $item->getKey(), 'extra_views' => 2])
        ->assertHasNoTableActionErrors();

    $grant = StudentItemViewGrant::query()->where('student_id', $student->getKey())->firstOrFail();
    expect($grant->extra_views)->toBe(2);

    $rm->loadTable()->assertCanSeeTableRecords([$grant])->callTableAction('revoke', $grant);
    expect(StudentItemViewGrant::query()->count())->toBe(0);
});

it('المشاهد مايشوفش زرار «مشاهدات زيادة»', function (): void {
    $student = sfStudent();
    actingAsRole('viewer');

    Livewire::test(ViewGrantsRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->assertTableActionHidden('grantViews');
});

// ═══════════ مواعيد ظهور العنصر ═══════════

it('العنصر قبل «يظهر من» وبعد «يظهر لحد» مخفي من صفحة الكورس والمشغّل ومقفول في ItemAccess', function (): void {
    $course = sfCourse();
    $later = sfLessonItem($course, [], ['name' => 'Future lesson', 'visible_from' => now()->addDay()]);
    $expired = sfLessonItem($course, [], ['name' => 'Old lesson', 'visible_until' => now()->subDay()]);
    $student = sfLogin();
    sfEnroll($student, $course);

    $this->get(route('site.courses.show', $course->slug))
        ->assertOk()
        ->assertSee('Intro video')
        ->assertDontSee('Future lesson')
        ->assertDontSee('Old lesson');

    $this->get(route('site.learn', [$course->slug, $later->getKey()]))->assertNotFound();
    $this->get(route('site.learn', [$course->slug, $expired->getKey()]))->assertNotFound();

    expect(app(ItemAccess::class)->denial($student, $later))->toBe('item_not_visible')
        ->and(app(ItemAccess::class)->denial($student, $expired))->toBe('item_not_visible');

    $this->travel(2)->days();

    $this->get(route('site.courses.show', $course->slug))->assertSee('Future lesson');
    $this->get(route('site.learn', [$course->slug, $later->getKey()]))->assertOk();
});

it('امتحان برا مواعيد ظهوره مايتبدأش حتى برابط مباشر', function (): void {
    $course = sfCourse();
    $item = sfExamItem($course);
    $item->update(['visible_from' => now()->addHour()]);
    sfEnroll(sfLogin(), $course);

    $this->get(route('site.exams.start', $item->getKey()))->assertForbidden();
    $this->post(route('site.exams.begin', $item->getKey()))->assertRedirect(route('site.exams.start', $item->getKey()));
});

// ═══════════ PDF: مسموح بالتنزيل ولا لأ ═══════════

function sfPdf(array $lesson = []): array
{
    Storage::fake(app(DiskResolver::class)->for('documents'));
    $course = sfCourse();
    $item = sfLessonItem($course, [...sfPdfLesson(), ...$lesson]);
    $item->lesson->addMedia(UploadedFile::fake()->createWithContent('notes.pdf', "%PDF-1.4\n%%EOF"))
        ->toMediaCollection('documents', app(DiskResolver::class)->for('documents'));
    sfEnroll(sfLogin(), $course);

    return [$course, $item->fresh(['lesson'])];
}

it('PDF من غير تنزيل (الافتراضي): معروض جوه الصفحة من غير زرار تنزيل، والملف inline دايمًا', function (): void {
    [$course, $item] = sfPdf();

    expect($item->lesson->allow_download)->toBeFalse();

    $this->get(route('site.learn', [$course->slug, $item->getKey()]))
        ->assertOk()
        ->assertSee('#toolbar=0', false)
        ->assertDontSee(__('storefront::storefront.learn.download_pdf'));

    $signed = URL::temporarySignedRoute('site.learn.document', now()->addMinutes(5), [$course->slug, $item->getKey(), 'download' => 1]);
    expect($this->get($signed)->assertOk()->headers->get('Content-Disposition'))->toStartWith('inline');
});

it('PDF مسموح تنزيله: زرار تنزيل والملف attachment', function (): void {
    [$course, $item] = sfPdf(['allow_download' => true]);

    $page = $this->get(route('site.learn', [$course->slug, $item->getKey()]))
        ->assertOk()
        ->assertSee(__('storefront::storefront.learn.download_pdf'));

    preg_match('~href="([^"]+download=1[^"]*)"~', (string) $page->getContent(), $m);
    expect($m[1] ?? null)->not->toBeNull();

    expect($this->get(html_entity_decode($m[1]))->assertOk()->headers->get('Content-Disposition'))->toStartWith('attachment');
});
