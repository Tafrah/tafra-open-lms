<?php

declare(strict_types=1);

use Livewire\Livewire;
use Src\Contexts\Audit\Domain\Models\ActivityLog;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

/*
|--------------------------------------------------------------------------
| معايير قبول سجل النشاط (docs/11 بند ٤ · docs/24 بند ٧)
|--------------------------------------------------------------------------
| نفس الشاشة في اللوحتين: في المؤسسة بتتفلتر بـ `properties.tenant_id`،
| وفي /central بتعرض الكل لفريق التشغيل.
*/

function activityFor(?Tenant $tenant, string $description): ActivityLog
{
    $activity = activity()
        ->withProperties($tenant === null ? [] : ['tenant_id' => $tenant->getKey()])
        ->log($description);

    return ActivityLog::query()->findOrFail($activity->getKey());
}

it('سجل المؤسسة بيعرض نشاطها بس', function (): void {
    actingAsRole('admin');

    $mine = activityFor(currentTenant(), 'نشاط المؤسسة');
    $other = activityFor(Tenant::factory()->create(), 'نشاط مؤسسة تانية');

    Livewire::test(ListActivityLogs::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$other]);
});

it('اللوحة المركزية بتعرض نشاط كل المؤسسات لفريق التشغيل', function (): void {
    $first = activityFor(Tenant::factory()->create(), 'نشاط أول');
    $second = activityFor(Tenant::factory()->create(), 'نشاط تاني');

    actingAsCentral('support');

    Livewire::test(ListActivityLogs::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$first, $second]);
});

it('السجل للقراءة فقط — مفيش شاشة إنشاء أو تعديل', function (): void {
    expect(array_keys(ActivityLogResource::getPages()))->toBe(['index']);
});

it('المحرر مايشوفش سجل النشاط', function (): void {
    actingAsRole('editor');

    $this->get(ActivityLogResource::getUrl('index'))->assertForbidden();
});
