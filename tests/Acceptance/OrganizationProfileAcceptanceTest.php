<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Livewire\Livewire;
use Src\Contexts\Tenancy\Presentation\Filament\Pages\EditOrganization;

/*
|--------------------------------------------------------------------------
| ملف المؤسسة من جوه لوحتها (docs/24 بند ٣)
|--------------------------------------------------------------------------
| شاشة المؤسسات بقت مركزية — الصفحة دي هي الطريق الوحيد لمدير المؤسسة
| يعدّل اسمها ولونها. `EditTenantProfile` مش بيتكتشف (`$isDiscovered = false`)
| فاختبارات التنقّل العامة مابتشوفهاش — عشان كده ليها اختبار لوحدها.
*/

it('مدير المؤسسة بيفتح ملفها ويحفظه', function (): void {
    actingAsRole('admin');
    $tenant = currentTenant();

    $this->get(EditOrganization::getUrl(tenant: $tenant))->assertOk();

    Livewire::test(EditOrganization::class)
        ->fillForm([
            'name' => ['ar' => 'الاسم الجديد', 'en' => 'New name'],
            'primary_color' => '#0F5132',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tenant->fresh()->getTranslation('name', 'en'))->toBe('New name')
        ->and($tenant->fresh()->primary_color)->toBe('#0F5132');
});

it('بيرفض لون تباينه ضعيف', function (): void {
    actingAsRole('admin');

    Livewire::test(EditOrganization::class)
        ->fillForm(['name' => ['ar' => 'اسم', 'en' => 'Name'], 'primary_color' => '#FFFF00'])
        ->call('save')
        ->assertHasFormErrors(['primary_color']);
});

it('المشاهد مايشوفش ملف المؤسسة — 404 مش 403', function (): void {
    actingAsRole('viewer');

    $this->get(EditOrganization::getUrl(tenant: currentTenant()))->assertNotFound();
});

it('رابط ملف المؤسسة ظاهر في قائمة المؤسسة للمدير', function (): void {
    actingAsRole('admin');

    $this->get(Filament::getPanel('admin')->getUrl(currentTenant()))
        ->assertOk()
        ->assertSee(EditOrganization::getUrl(tenant: currentTenant()), escape: false);
});
