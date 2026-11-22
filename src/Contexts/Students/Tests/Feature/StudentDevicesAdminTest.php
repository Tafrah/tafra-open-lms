<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Src\Contexts\Settings\Application\Services\TenantSettings;
use Src\Contexts\Storefront\Application\Queries\SiteSettings;
use Src\Contexts\Storefront\Presentation\Filament\Pages\ManageSiteSettings;
use Src\Contexts\Students\Application\Actions\ManageStudentAction;
use Src\Contexts\Students\Domain\Models\Student;
use Src\Contexts\Students\Domain\Models\StudentDevice;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\EditStudent;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\Pages\ListStudents;
use Src\Contexts\Students\Presentation\Filament\Resources\StudentResource\RelationManagers\DevicesRelationManager;

/*
| أجهزة الطالب في اللوحة: المدرس بيشوفها ويفصلها كلها، والحد من «إعدادات الموقع».
*/

function stdDevice(Student $student, string $name = 'Chrome · Android'): StudentDevice
{
    return StudentDevice::query()->create([
        'student_id' => $student->getKey(),
        'token_hash' => StudentDevice::hashToken(uniqid('t', true)),
        'device_name' => $name,
        'ip_address' => '10.0.0.1',
        'last_seen_at' => now(),
    ]);
}

it('المدير بيشوف أجهزة الطالب في صفحته', function (): void {
    actingAsRole('admin');
    $student = Student::factory()->create();
    $device = stdDevice($student, 'Safari · iPhone');
    $other = stdDevice(Student::factory()->create(), 'Firefox · Windows');

    Livewire::test(DevicesRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->assertOk()
        ->loadTable()
        ->assertCanSeeTableRecords([$device])
        ->assertCanNotSeeTableRecords([$other])
        ->assertSee('Safari · iPhone');
});

it('«افصل كل الأجهزة» من صفحة الطالب بيمسح أجهزته هو بس ويلغي «افتكرني»', function (): void {
    actingAsRole('admin');
    $student = Student::factory()->create();
    $student->forceFill(['remember_token' => 'remember-me'])->save();
    stdDevice($student);
    stdDevice($student);
    $keep = stdDevice(Student::factory()->create());

    Livewire::test(DevicesRelationManager::class, ['ownerRecord' => $student, 'pageClass' => EditStudent::class])
        ->callAction(TestAction::make('disconnect_devices')->table())
        ->assertHasNoErrors();

    expect($student->devices()->count())->toBe(0)
        ->and($student->fresh()->remember_token)->toBeNull()
        ->and($keep->fresh())->not->toBeNull();
});

it('«افصل كل الأجهزة» موجود في جدول الطلبة كمان', function (): void {
    actingAsRole('admin');
    $student = Student::factory()->create();
    stdDevice($student);

    Livewire::test(ListStudents::class)
        ->loadTable()
        ->callAction(TestAction::make('disconnect_devices')->table($student));

    expect($student->devices()->count())->toBe(0);
});

it('المشاهد مايشوفش الأجهزة ولا زرار الفصل', function (): void {
    actingAsRole('viewer');
    $student = Student::factory()->create();

    expect(DevicesRelationManager::canViewForRecord($student, EditStudent::class))->toBeFalse();

    Livewire::test(ListStudents::class)
        ->loadTable()
        ->assertActionHidden(TestAction::make('disconnect_devices')->table($student));
});

it('المساعد يقدر يفصل الأجهزة (بيتابع الطلبة)', function (): void {
    $assistant = actingAsRole('assistant');
    $student = Student::factory()->create();
    stdDevice($student);

    expect(DevicesRelationManager::canViewForRecord($student, EditStudent::class))->toBeTrue();

    app(ManageStudentAction::class)->disconnectDevices($student, $assistant);
    expect($student->devices()->count())->toBe(0);
});

it('الـ Action نفسه بيرفض من غير صلاحية — مش بس الزرار مخفي', function (): void {
    $viewer = userWithRole('viewer');
    $student = Student::factory()->create();
    stdDevice($student);

    expect(fn () => app(ManageStudentAction::class)->disconnectDevices($student, $viewer))
        ->toThrow(AuthorizationException::class);
    expect($student->devices()->count())->toBe(1);
});

it('المدير بيحدد أقصى عدد أجهزة من «إعدادات الموقع» (الافتراضي ١، 0 = مفتوح)', function (): void {
    actingAsRole('admin');

    Livewire::test(ManageSiteSettings::class)
        ->assertSet('data.max_devices_per_student', 1)
        ->fillForm(['max_devices_per_student' => 3])
        ->call('save')
        ->assertHasNoFormErrors();

    app(SiteSettings::class)->flush();
    expect(app(SiteSettings::class)->load()->maxDevicesPerStudent)->toBe(3);

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['max_devices_per_student' => -1])
        ->call('save')
        ->assertHasFormErrors(['max_devices_per_student']);

    app(TenantSettings::class)->set('site', 'max_devices_per_student', 0);
    app(SiteSettings::class)->flush();
    expect(app(SiteSettings::class)->load()->maxDevicesPerStudent)->toBe(0);
});
