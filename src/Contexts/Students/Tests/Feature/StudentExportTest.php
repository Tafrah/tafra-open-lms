<?php

declare(strict_types=1);

use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\EducationalLevel;
use Src\Contexts\Billing\Application\Actions\EnrollStudentAction;
use Src\Contexts\Billing\Domain\Enums\EnrollmentSource;
use Src\Contexts\Students\Domain\Enums\SchoolType;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Infrastructure\Exports\StudentExporter;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\ListStudents;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Application\Contracts\TenantContext;

/*
| تصدير الطلبة Excel/CSV من جدول الطلبة — بصلاحية `export.students`.
*/

beforeEach(fn () => Storage::fake(config('filesystems.default')));

it('زرار التصدير للمدير بس — المساعد والمشاهد مالهمش تصدير', function (string $role, bool $visible): void {
    actingAsRole($role);

    $test = Livewire::test(ListStudents::class);

    $visible ? $test->assertActionVisible('export') : $test->assertActionHidden('export');
})->with([
    ['admin', true],
    ['assistant', false],
    ['viewer', false],
]);

it('الأعمدة: الاسم، الموبايل، موبايل ولي الأمر، الصف، نوع التعليم، الرصيد، عدد الكورسات، تاريخ التسجيل', function (): void {
    actingAsRole('admin');

    $columns = array_map(fn ($column): string => $column->getName(), StudentExporter::getColumns());

    expect($columns)->toBe(['name', 'phone', 'parent_phone', 'level.name', 'school_type', 'balance', 'enrolled_courses', 'created_at']);
});

it('التصدير بيطلّع طلبة المؤسسة دي بقيمهم (والكورسات = المشترك فيها دلوقتي)', function (): void {
    $admin = actingAsRole('admin');
    $level = EducationalLevel::factory()->create(['name' => 'الصف الثالث الثانوي']);
    $student = Student::factory()->create([
        'name' => 'Mona Adel', 'phone' => '01011112222', 'parent_phone' => '01233334444',
        'educational_level_id' => $level->getKey(), 'school_type' => SchoolType::Azhar,
    ]);
    $first = Bootcamp::factory()->create();
    $second = Bootcamp::factory()->create();
    app(EnrollStudentAction::class)->handle($student, $first, EnrollmentSource::Admin);
    app(EnrollStudentAction::class)->handle($student, $second, EnrollmentSource::Admin);
    app(EnrollStudentAction::class)->revoke($student, $second, (int) $admin->getKey());

    $other = Tenant::factory()->create();
    app(TenantContext::class)->forTenant($other->getKey(), fn () => Student::factory()->create(['name' => 'Foreign Student']));

    Livewire::test(ListStudents::class)
        ->callAction('export', data: ['format' => 'csv'])
        ->assertHasNoActionErrors();

    $export = Export::query()->latest('id')->firstOrFail();
    expect($export->exporter)->toBe(StudentExporter::class)
        ->and($export->successful_rows)->toBe(1);

    $csv = collect(Storage::disk($export->file_disk)->files($export->getFileDirectory()))
        ->map(fn (string $file): string => (string) Storage::disk($export->file_disk)->get($file))
        ->implode("\n");

    expect($csv)->toContain('Mona Adel')
        ->toContain('01011112222')
        ->toContain('01233334444')
        ->toContain('الصف الثالث الثانوي')
        ->toContain(SchoolType::Azhar->getLabel())
        ->not->toContain('Foreign Student')
        ->and(str_contains($csv, ',1,') || str_contains($csv, ',"1",'))->toBeTrue();
});
