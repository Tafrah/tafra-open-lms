<?php

declare(strict_types=1);

use Filament\Actions\Exports\Models\Export;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Exams\Application\Actions\ResetStudentExamAttemptsAction;
use Src\Contexts\Exams\Application\Actions\StartAttemptAction;
use Src\Contexts\Exams\Application\Actions\SubmitAttemptAction;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\Question;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource\Pages\ListOnlinePassedExams;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\EditStudent;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers\AttemptsRelationManager;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

beforeEach(fn () => inTenant());

function usedUpExam(Student $student, int $chances = 1): OnlineExam
{
    $exam = OnlineExam::factory()->create(['allowed_chances' => $chances]);
    Question::factory()->withChoices()->create(['online_exam_id' => $exam->getKey()]);

    for ($i = 0; $i < $chances; $i++) {
        app(SubmitAttemptAction::class)->handle(app(StartAttemptAction::class)->handle($student, $exam));
    }

    return $exam;
}

it('إعادة المحاولات بتمسح محاولات الطالب في الامتحان ده بس، فيقدر يدخله تاني', function (): void {
    $admin = actingAsRole('admin');
    $student = Student::factory()->create();
    $exam = usedUpExam($student);
    $other = usedUpExam($student);
    $classmate = Student::factory()->create();
    app(SubmitAttemptAction::class)->handle(app(StartAttemptAction::class)->handle($classmate, $exam));

    expect(fn () => app(StartAttemptAction::class)->handle($student, $exam))->toThrow(ExamsException::class);

    $deleted = app(ResetStudentExamAttemptsAction::class)->handle($student, (int) $exam->getKey(), $admin);

    expect($deleted)->toBe(1)
        ->and(OnlinePassedExam::query()->where('student_id', $student->getKey())->where('online_exam_id', $exam->getKey())->count())->toBe(0)
        ->and(OnlinePassedExam::query()->where('student_id', $student->getKey())->where('online_exam_id', $other->getKey())->count())->toBe(1)
        ->and(OnlinePassedExam::query()->where('student_id', $classmate->getKey())->count())->toBe(1)
        ->and(StudentExamAnswer::query()->where('student_id', $student->getKey())->count())->toBe(1);

    $fresh = app(StartAttemptAction::class)->handle($student, $exam);
    expect($fresh->current_student_chance)->toBe(1);
});

it('المساعد يقدر يعيد المحاولات، والمشاهد لأ', function (): void {
    $student = Student::factory()->create();
    $exam = usedUpExam($student);

    $viewer = actingAsRole('viewer');
    expect(fn () => app(ResetStudentExamAttemptsAction::class)->handle($student, (int) $exam->getKey(), $viewer))
        ->toThrow(AuthorizationException::class);

    $assistant = actingAsRole('assistant');
    expect(app(ResetStudentExamAttemptsAction::class)->handle($student, (int) $exam->getKey(), $assistant))->toBe(1);
});

it('زرار «إعادة محاولات الطالب» في شاشة المحاولات وفي صفحة الطالب', function (): void {
    actingAsRole('admin');
    $student = Student::factory()->create();
    $exam = usedUpExam($student, chances: 2);
    $attempt = OnlinePassedExam::query()->where('online_exam_id', $exam->getKey())->firstOrFail();

    Livewire::test(ListOnlinePassedExams::class)
        ->loadTable()
        ->assertTableActionVisible('resetAttempts', $attempt)
        ->callTableAction('resetAttempts', $attempt)
        ->assertNotified();

    expect(OnlinePassedExam::query()->where('online_exam_id', $exam->getKey())->count())->toBe(0);

    $again = usedUpExam($student);
    $row = OnlinePassedExam::query()->where('online_exam_id', $again->getKey())->firstOrFail();

    Livewire::test(AttemptsRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->loadTable()
        ->callTableAction('resetAttempts', $row);

    expect(OnlinePassedExam::query()->where('online_exam_id', $again->getKey())->count())->toBe(0);
});

it('المشاهد مايشوفش زرار إعادة المحاولات', function (): void {
    $student = Student::factory()->create();
    $exam = usedUpExam($student);
    actingAsRole('viewer');

    Livewire::test(ListOnlinePassedExams::class)
        ->loadTable()
        ->assertTableActionHidden('resetAttempts', OnlinePassedExam::query()->where('online_exam_id', $exam->getKey())->firstOrFail());
});

it('محاولة مؤسسة تانية مابتتمسحش', function (): void {
    $admin = actingAsRole('admin');
    $other = Tenant::factory()->create();
    [$student, $exam] = app(TenantContext::class)->forTenant($other->getKey(), function (): array {
        $student = Student::factory()->create();

        return [$student, usedUpExam($student)];
    });

    expect(fn () => app(ResetStudentExamAttemptsAction::class)->handle($student, (int) $exam->getKey(), $admin))
        ->not->toThrow(Throwable::class);

    expect(app(TenantContext::class)->forTenant($other->getKey(), fn (): int => OnlinePassedExam::query()->count()))->toBe(1);
});

it('تصدير النتايج: الاسم والموبايل والامتحان والدرجة والنسبة والنجاح ووقت التسليم', function (): void {
    Storage::fake(config('filament.default_filesystem_disk', 'local'));
    actingAsRole('admin');
    $student = Student::factory()->create(['name' => 'Mona Ali', 'phone' => '01012345678']);
    $exam = OnlineExam::factory()->create(['name' => 'Algebra quiz', 'min_percentage' => 50]);
    OnlinePassedExam::factory()->create([
        'student_id' => $student->getKey(), 'online_exam_id' => $exam->getKey(), 'finished' => true,
        'student_total_mark' => 8, 'max_total_mark' => 10, 'student_percentage' => 80, 'end_at' => now(),
    ]);
    OnlinePassedExam::factory()->create(['finished' => false]); // مفتوحة — مابتتصدّرش

    Livewire::test(ListOnlinePassedExams::class)
        ->callAction('export')
        ->assertHasNoActionErrors();

    $export = Export::query()->latest('id')->firstOrFail();
    expect($export->total_rows)->toBe(1);

    $disk = Storage::disk($export->file_disk);
    $csv = collect($disk->allFiles($export->getFileDirectory()))
        ->filter(fn (string $f): bool => str_ends_with($f, '.csv'))
        ->map(fn (string $f): string => (string) $disk->get($f))
        ->implode("\n");

    expect($csv)->toContain('Mona Ali')
        ->toContain('01012345678')
        ->toContain('Algebra quiz')
        ->toContain('80')
        ->toContain(__('exams::exams.attempt.export.yes'));
});

it('المساعد مالوش زرار التصدير', function (): void {
    actingAsRole('assistant');

    Livewire::test(ListOnlinePassedExams::class)->assertActionHidden('export');
});
