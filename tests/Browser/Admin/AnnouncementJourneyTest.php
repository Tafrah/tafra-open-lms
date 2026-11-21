<?php

declare(strict_types=1);

use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Content\Presentation\Filament\Resources\AnnouncementResource;

/*
|--------------------------------------------------------------------------
| رحلة الإعلانات كاملة (docs/17 بند ١٣)
|--------------------------------------------------------------------------
| الشاشة المرجعية للـ CRUD: الحالة الفاضية ← إنشاء ← تعديل ← حذف بتأكيد.
| أي ميزة جديدة فيها CRUD تنسخ الملف ده.
*/

/** الحقل الظاهر بس — حقول الترجمة التانية موجودة في تبويبات مخفية. */
function visibleField(string $suffix): string
{
    return "[id\$=\"{$suffix}\"]:visible";
}

it('المدير بيعمل دورة الإعلان كاملة من السايدبار', function (): void {
    actingAsRole('admin');

    $list = screenPath(AnnouncementResource::class);

    $page = journeyToScreen(adminHome(), $list, 'announcements.list');

    // الحالة الفاضية التعليمية مع زرار الإنشاء
    $page->assertVisible('.fi-ta-empty-state')
        ->assertSee(__('content::content.announcement.empty.cta'))
        ->click(linkNamed(__('content::content.announcement.empty.cta'), '.fi-ta-empty-state'))
        ->assertPathEndsWith('/create');

    fillTranslatable($page, 'title', 'إعلان رحلة المستخدم')
        ->fill('.fi-fo-rich-editor [contenteditable="true"]:visible', 'نص الإعلان')
        ->click(buttonNamed(__('filament-panels::resources/pages/create-record.form.actions.create.label')));

    $announcement = eventually($page, fn (): ?Announcement => Announcement::query()->latest('id')->first());

    expect($announcement)->toBeInstanceOf(Announcement::class, 'الإعلان ماتحفظش بعد الإنشاء');

    expect($announcement->getTranslation('title', config('app.fallback_locale')))->toBe('إعلان رحلة المستخدم');

    // التعديل
    $edit = visit((string) parse_url(AnnouncementResource::getUrl('edit', ['record' => $announcement]), PHP_URL_PATH));

    $edit->click(localeTabSelector((string) config('app.fallback_locale')))
        ->clear(visibleField('title.'.config('app.fallback_locale')))
        ->fill(visibleField('title.'.config('app.fallback_locale')), 'إعلان بعد التعديل')
        ->click(buttonNamed(__('filament-panels::resources/pages/edit-record.form.actions.save.label')));

    expect(eventually($edit, fn (): bool => $announcement->fresh()->getTranslation('title', config('app.fallback_locale')) === 'إعلان بعد التعديل'))
        ->toBeTrue('التعديل ماتحفظش');

    assertUiQuality($edit);

    // الحذف بتأكيد — مودال مش حذف مباشر
    $edit->click(buttonNamed(__('filament-actions::delete.single.label'), '.fi-header'))
        ->assertVisible('.fi-modal-window:visible')
        ->click(buttonNamed(__('filament-actions::delete.single.modal.actions.delete.label'), '.fi-modal-window:visible .fi-modal-footer-actions'));

    expect(eventually($edit, fn (): bool => Announcement::withTrashed()->findOrFail($announcement->getKey())->trashed()))
        ->toBeTrue('الإعلان ماتحذفش بعد التأكيد');

    visit($list)->assertDontSee('إعلان بعد التعديل');
});

it('القائمة سليمة على موبايل بالوضع الداكن وبالإنجليزي', function (): void {
    $user = actingAsRole('admin');
    $user->forceFill(['locale' => 'en'])->save();

    Announcement::factory()->count(3)->create();

    assertMobileDarkQuality(screenPath(AnnouncementResource::class), 'en');
});

it('المشاهد مالوش إنشاء — لا زرار ولا صفحة', function (): void {
    actingAsRole('viewer');

    visit(screenPath(AnnouncementResource::class))
        ->assertDontSee(__('content::content.announcement.empty.cta'));

    assertScreenDenied(adminHome(), (string) parse_url(AnnouncementResource::getUrl('create'), PHP_URL_PATH));
});
