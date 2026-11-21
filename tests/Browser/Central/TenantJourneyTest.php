<?php

declare(strict_types=1);

use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Presentation\Filament\Central\Resources\TenantResource;
use Src\Support\Application\Contracts\TenantContext;

/*
|--------------------------------------------------------------------------
| رحلة المؤسسات من /central (docs/24 · docs/17 بند ١٣)
|--------------------------------------------------------------------------
| الإنشاء ← الإيقاف بسبب ← الانتحال لحد لوحة المؤسسة. الانتحال بالذات مالوش
| تغطية تانية على زرار الجدول نفسه و`$action->redirect()` — Livewire مابيتبعش
| التحويل لحد الصفحة.
*/

/** زرار تأكيد المودال المفتوح — «تأكيد» للتأكيد البسيط و«إرسال» للمودال اللي فيه حقول. */
function confirmModal(object $page, string $labelKey = 'filament-actions::modal.actions.confirm.label'): object
{
    return $page->assertVisible('.fi-modal-window:visible')
        ->click(buttonNamed(__($labelKey), '.fi-modal-window:visible .fi-modal-footer-actions'))
        ->wait(2);
}

/**
 * عنصر من قائمة إجراءات الصف.
 *
 * الإجراء اللي الـ Policy رافضاه بيظهر `aria-disabled` مع سبب في tooltip —
 * Playwright مابيضغطش عليه وبيستنى لحد المهلة. التأكيد الأول بيحوّل ده
 * لرسالة واضحة بدل «Timeout 5000ms».
 */
function rowAction(object $page, string $rowText, string $label): object
{
    $item = buttonNamed($label, '.fi-dropdown-panel');

    return $page->click(buttonNamed(__('common.actions'), ".fi-ta-row:has-text(\"{$rowText}\")"))
        ->assertAttributeMissing($item, 'aria-disabled')
        ->click($item);
}

it('فريق التشغيل بينشئ مؤسسة من الحالة الفاضية', function (): void {
    actingAsCentral('super_admin');

    $page = journeyToScreen('/central', screenPath(TenantResource::class, 'central'), 'tenancy.tenants');

    $page->assertVisible('.fi-ta-empty-state')
        ->click(linkNamed(__('filament-actions::create.single.label', ['label' => TenantResource::getModelLabel()]), '.fi-ta-empty-state'))
        ->assertPathEndsWith('/create');

    fillTranslatable($page, 'name', 'مؤسسة الرحلة')
        ->fill('[id$="slug"]', 'journey-org')
        ->click(buttonNamed(__('filament-panels::resources/pages/create-record.form.actions.create.label')));

    $tenant = eventually($page, fn (): ?Tenant => Tenant::query()->where('slug', 'journey-org')->first(), 20.0);

    expect($tenant)->toBeInstanceOf(Tenant::class, 'المؤسسة ماتحفظتش بعد الإنشاء');

    expect($tenant->getTranslation('name', config('app.fallback_locale')))->toBe('مؤسسة الرحلة');

    assertUiQuality($page);
});

it('فريق التشغيل بيوقف مؤسسة بسبب وبعدين يدخلها بالانتحال', function (): void {
    // مؤسسة نشطة تانية: إيقاف آخر مؤسسة نشطة ممنوع (TenantPolicy::isLastActive)
    Tenant::factory()->create(['name' => ['ar' => 'مؤسسة أخرى', 'en' => 'Other']]);

    $tenant = Tenant::factory()->create(['name' => ['ar' => 'مؤسسة الإيقاف', 'en' => 'To suspend']]);
    userWithRole('admin', $tenant);

    actingAsCentral('super_admin');
    app(TenantContext::class)->set(null);

    $page = visit(screenPath(TenantResource::class, 'central'));
    $page->click('.driver-popover-close-btn')->wait(1);

    // الإيقاف: السبب إلزامي
    rowAction($page, $tenant->slug, __('tenancy::tenancy.tenant.actions.suspend'))
        ->fill('.fi-modal-window:visible textarea', 'متأخرات في السداد');

    confirmModal($page, 'filament-actions::modal.actions.submit.label');

    expect(eventually($page, fn (): bool => $tenant->fresh()->isSuspended()))->toBeTrue('المؤسسة ماتوقفتش');

    // الانتحال مسموح على المؤسسة الموقوفة — بيوصل لوحة المؤسسة نفسها
    rowAction($page, $tenant->slug, __('tenancy::tenancy.tenant.actions.impersonate'));

    confirmModal($page)
        ->assertPathBeginsWith('/admin/t/'.$tenant->slug)
        ->assertPresent('.fi-sidebar');

    assertUiQuality($page);
});

it('قائمة المؤسسات سليمة على موبايل بالوضع الداكن وبالإنجليزي', function (): void {
    Tenant::factory()->count(3)->create();

    $user = actingAsCentral('super_admin');
    $user->forceFill(['locale' => 'en'])->save();

    assertMobileDarkQuality(screenPath(TenantResource::class, 'central'), 'en');
});

it('مستخدم المؤسسة مالوش /central خالص', function (): void {
    actingAsRole('admin');

    visit('/central/tenants')->assertNotPresent('.fi-sidebar');
});
