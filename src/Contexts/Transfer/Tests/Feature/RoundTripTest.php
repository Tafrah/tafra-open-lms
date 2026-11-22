<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;
use Src\Contexts\Billing\Application\Actions\CheckoutAction;
use Src\Contexts\Billing\Application\Actions\GenerateRechargeCardsAction;
use Src\Contexts\Billing\Application\Actions\RedeemRechargeCardAction;
use Src\Contexts\Billing\Application\DataObjects\RechargeCardBatchData;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Billing\Domain\Enums\PaymentGateway;
use Src\Contexts\Billing\Domain\Models\Coupon;
use Src\Contexts\Billing\Domain\Models\Enrollment;
use Src\Contexts\Billing\Domain\Models\MoneyTransaction;
use Src\Contexts\Exams\Application\Actions\SaveAnswerAction;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Models\Choice;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Transfer\Application\Actions\ExportAcademyAction;
use Src\Contexts\Transfer\Application\Actions\ImportAcademyAction;
use Src\Contexts\Transfer\Domain\Exceptions\TransferException;
use Src\Support\Application\Contracts\DiskResolver;
use Src\Support\Application\Contracts\TenantContext;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

/** أكاديمية فيها كل حاجة: كورس بدرس وامتحان، طالب اشترى بالمحفظة، كود اتستخدم، محاولة امتحان */
function seedAcademy(): array
{
    $admin = userWithRole('admin');
    $level = EducationalLevel::factory()->create(['code' => '3SEC', 'name' => 'تالتة ثانوي']);
    $lesson = OnlineLesson::factory()->create(['name' => 'الدرس الأول']);
    $text = OnlineLesson::factory()->text()->create(['name' => 'ملخص']);
    $bootcamp = Bootcamp::factory()->create(['name' => 'كيمياء', 'slug' => 'chemistry', 'price' => 100, 'educational_level_id' => $level->getKey()]);
    $bootcamp->addMedia(UploadedFile::fake()->image('cover.jpg', 640, 360))->toMediaCollection('thumbnail', 'public');

    $exam = OnlineExam::factory()->create(['name' => 'امتحان الباب الأول', 'allowed_chances' => 3]);
    $mcq = Question::factory()->withChoices()->create(['online_exam_id' => $exam->getKey(), 'order' => 1, 'mark' => 2]);
    Question::factory()->essay()->create(['online_exam_id' => $exam->getKey(), 'order' => 2]);

    $section = BootcampSection::factory()->create(['bootcamp_id' => $bootcamp->getKey(), 'name' => 'الباب الأول']);
    BootcampSectionItem::factory()->create(['bootcamp_section_id' => $section->getKey(), 'online_lesson_id' => $lesson->getKey(), 'order' => 1]);
    BootcampSectionItem::factory()->create(['bootcamp_section_id' => $section->getKey(), 'online_lesson_id' => $text->getKey(), 'order' => 2]);
    BootcampSectionItem::factory()->create(['bootcamp_section_id' => $section->getKey(), 'type' => SectionItemType::Exam, 'online_lesson_id' => null, 'online_exam_id' => $exam->getKey(), 'order' => 3]);

    $student = Student::factory()->create(['phone' => '01012345678', 'password' => 'secret-pass', 'educational_level_id' => $level->getKey()]);
    $group = app(GenerateRechargeCardsAction::class)->handle(RechargeCardBatchData::fromArray(['name' => 'سنتر', 'type' => 'wallet', 'amount' => 150, 'count' => 3]), $admin);
    app(RedeemRechargeCardAction::class)->handle($student, $group->cards()->first()->code);
    Coupon::factory()->create(['code' => 'TEN', 'type' => 'fixed', 'value' => 10]);
    app(CheckoutAction::class)->handle($student, $bootcamp, PaymentGateway::Wallet, 'TEN');

    $attempt = app(StartAttemptAction::class)->handle($student, $exam);
    app(SaveAnswerAction::class)->handle($student, $attempt, $mcq->getKey(), $mcq->choices()->where('is_correct', true)->value('id'));
    app(SubmitAttemptAction::class)->handle($attempt);

    return compact('admin', 'student', 'bootcamp', 'exam');
}

it('تصدير ثم استيراد في أكاديمية تانية بيرجّع كل حاجة', function (): void {
    $source = inTenant();
    $seed = seedAcademy();
    $zip = storage_path('app/testing/export.zip');

    app(ExportAcademyAction::class)->handle($zip);
    expect(is_file($zip))->toBeTrue();

    $target = Tenant::factory()->create();
    app(TenantContext::class)->set($target->getKey());
    $report = app(ImportAcademyAction::class)->handle($zip);

    expect($report->tables['students']['imported'])->toBe(1)
        ->and($report->tables['bootcamps']['imported'])->toBe(1)
        ->and($report->tables['questions']['imported'])->toBe(2)
        ->and($report->tables['bootcamp_section_items']['imported'])->toBe(3)
        ->and($report->tables['recharge_cards']['imported'])->toBe(3)
        ->and($report->media)->toBe(1);

    $student = Student::query()->where('phone', '01012345678')->firstOrFail();
    $bootcamp = Bootcamp::query()->where('slug', 'chemistry')->firstOrFail();

    // نفس كلمة السر بتشتغل — الهاش اتنقل زي ما هو
    expect(Hash::check('secret-pass', $student->password))->toBeTrue()
        ->and((float) $student->balance)->toBe(60.0)
        ->and(app(BootcampAccess::class)->has($student, $bootcamp))->toBeTrue()
        ->and($bootcamp->sections()->first()->items()->count())->toBe(3)
        ->and($bootcamp->getFirstMedia('thumbnail'))->not->toBeNull()
        ->and($student->attempts()->first()->answers()->count())->toBe(2)
        ->and(OnlineExam::query()->first()->questions()->first()->choices()->count())->toBe(4)
        ->and($student->level?->code)->toBe('3SEC');

    // الأكاديمية الأصلية ماتلمستش
    app(TenantContext::class)->set($source->getKey());
    expect(Student::query()->count())->toBe(1)->and(Bootcamp::query()->count())->toBe(1);
});

it('التجربة مابتحفظش حاجة لكن بتدّي تقرير حقيقي', function (): void {
    inTenant();
    seedAcademy();
    $zip = storage_path('app/testing/export.zip');
    app(ExportAcademyAction::class)->handle($zip);

    $target = Tenant::factory()->create();
    app(TenantContext::class)->set($target->getKey());
    $report = app(ImportAcademyAction::class)->handle($zip, dryRun: true);

    expect($report->dryRun)->toBeTrue()
        ->and($report->tables['students']['imported'])->toBe(1)
        ->and(Student::query()->count())->toBe(0)
        ->and(Bootcamp::query()->count())->toBe(0);
});

it('نسخة تانية من نفس الأكاديمية (تصدير تاني) بتدمج الطالب بالموبايل ومابتكررش الأكواد', function (): void {
    inTenant();
    seedAcademy();
    $zip = storage_path('app/testing/export.zip');
    app(ExportAcademyAction::class)->handle($zip);

    $target = Tenant::factory()->create();
    app(TenantContext::class)->set($target->getKey());
    app(ImportAcademyAction::class)->handle($zip);

    // تصدير جديد من نفس المصدر (ملف مختلف) — مش نفس الملف
    app(TenantContext::class)->set(Tenant::query()->orderBy('id')->first()->getKey());
    $this->travel(1)->minutes();
    $zip2 = storage_path('app/testing/export2.zip');
    app(ExportAcademyAction::class)->handle($zip2);
    app(TenantContext::class)->set($target->getKey());
    $second = app(ImportAcademyAction::class)->handle($zip2);

    expect(Student::query()->count())->toBe(1)
        ->and($second->tables['students']['merged'])->toBe(1)
        ->and($second->tables['recharge_cards']['skipped'])->toBe(3)
        ->and(Bootcamp::query()->count())->toBe(2) // الرابط المكرر بياخد لاحقة
        ->and(Bootcamp::query()->pluck('slug')->unique()->count())->toBe(2);
});

it('ملف متلاعب فيه بيترفض قبل ما يلمس القاعدة', function (): void {
    inTenant();
    seedAcademy();
    $zip = storage_path('app/testing/export.zip');
    app(ExportAcademyAction::class)->handle($zip);

    $archive = new ZipArchive;
    $archive->open($zip);
    $archive->addFromString('data/students.ndjson', '{"id":1,"user_id":1000001,"code":"HACK"}'."\n");
    $archive->close();

    $target = Tenant::factory()->create();
    app(TenantContext::class)->set($target->getKey());

    expect(fn () => app(ImportAcademyAction::class)->handle($zip))->toThrow(TransferException::class);
    expect(Student::query()->count())->toBe(0);
});

it('درس من نوع مش مدعوم (من طفرة) بيتساب ويتسجّل في التقرير', function (): void {
    $work = storage_path('app/testing/tafra');
    @mkdir($work.'/data', 0777, true);
    file_put_contents($work.'/data/online_lessons.ndjson', json_encode(['id' => 9, 'name' => 'Bunny', 'type' => 'BunnyVideo'])."\n".json_encode(['id' => 10, 'name' => 'YT', 'type' => LessonType::YoutubeVideo->value, 'external_url' => 'https://youtu.be/dQw4w9WgXcQ'])."\n");
    file_put_contents($work.'/manifest.json', json_encode(['format' => 'tafra-academy-export', 'format_version' => 1, 'counts' => ['online_lessons' => 2, 'scorm_packages' => 3]]));
    $zip = storage_path('app/testing/tafra.zip');
    $archive = new ZipArchive;
    $archive->open($zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFile($work.'/manifest.json', 'manifest.json');
    $archive->addFile($work.'/data/online_lessons.ndjson', 'data/online_lessons.ndjson');
    $archive->close();

    inTenant();
    $report = app(ImportAcademyAction::class)->handle($zip);

    expect($report->tables['online_lessons'])->toMatchArray(['read' => 2, 'imported' => 1, 'skipped' => 1])
        ->and(implode("\n", $report->warnings))->toContain('BunnyVideo')->toContain('scorm_packages')
        ->and(OnlineLesson::query()->count())->toBe(1);
});

it('نفس الملف مايتستوردش مرتين (والتجربة بتقول كده)', function (): void {
    inTenant();
    seedAcademy();
    $zip = storage_path('app/testing/export.zip');
    app(ExportAcademyAction::class)->handle($zip);

    $target = Tenant::factory()->create();
    app(TenantContext::class)->set($target->getKey());
    app(ImportAcademyAction::class)->handle($zip);
    $counts = fn (): array => [
        (float) Student::query()->first()->balance,
        MoneyTransaction::query()->count(),
        Enrollment::query()->count(),
        OnlinePassedExam::query()->count(),
    ];
    $first = $counts();

    expect(fn () => app(ImportAcademyAction::class)->handle($zip))->toThrow(TransferException::class)
        ->and($counts())->toBe($first);
});

it('ترتيب الأسئلة والاختيار المختار في المحاولة بيتترجموا للـ IDs الجديدة', function (): void {
    inTenant();
    seedAcademy();
    $zip = storage_path('app/testing/export.zip');
    app(ExportAcademyAction::class)->handle($zip);

    $target = Tenant::factory()->create();
    app(TenantContext::class)->set($target->getKey());
    Question::factory()->count(5)->create(); // الأكاديمية فيها بيانات — الـ IDs بتتزحلق
    app(ImportAcademyAction::class)->handle($zip);

    $attempt = Student::query()->where('phone', '01012345678')->firstOrFail()->attempts()->first();
    $examQuestions = Question::query()->where('online_exam_id', $attempt->online_exam_id)->pluck('id')->sort()->values()->all();
    $chosen = $attempt->answers()->get()->firstWhere(fn ($a) => isset($a->answer['choice_id']))->answer['choice_id'];

    expect(collect($attempt->question_order)->sort()->values()->all())->toBe($examQuestions)
        ->and(Choice::query()->whereKey($chosen)->value('is_correct'))->toBeTrue();
});

it('اللوجو وصورة المدرس بيتنقلوا مع الملف (مش مسار لملف مش موجود)', function (): void {
    inTenant();
    $disk = Storage::disk(app(DiskResolver::class)->for('branding'));
    $disk->put('site/logo-old.png', UploadedFile::fake()->image('logo.png', 120, 40)->getContent());
    app(TenantSettings::class)->set('site', 'logo', 'site/logo-old.png');
    app(TenantSettings::class)->set('site', 'teacher_photo', 'site/missing.png');

    $zip = storage_path('app/testing/export-branding.zip');
    app(ExportAcademyAction::class)->handle($zip);

    $target = Tenant::factory()->create();
    app(TenantContext::class)->set($target->getKey());
    app(ImportAcademyAction::class)->handle($zip);

    $settings = app(TenantSettings::class);
    $logo = $settings->get('site', 'logo');

    expect($logo)->toBeString()->not->toBe('site/logo-old.png')
        ->and($disk->exists((string) $logo))->toBeTrue()
        // الصورة اللي ملفها مش موجود أصلاً بتتفضّى بدل ما تفضل مكسورة
        ->and($settings->get('site', 'teacher_photo'))->toBeNull();
});
