<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Src\Contexts\Settings\Presentation\Filament\Central\Clusters\SettingsCluster;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageAppearance;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageGeneral;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageMail;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageNotifications;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageSecurity;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageStorage;

/*
|--------------------------------------------------------------------------
| الإعدادات العامة = عنصر واحد في سايدبار /central + تبويبات (docs/05 بند ٥)
|--------------------------------------------------------------------------
| التبويبات بتتفلتر بصلاحية كل صفحة، والـ cluster نفسه بيرفض بـ 403
| لو مفيش ولا تبويب من حق المستخدم — الإخفاء مش أمان (docs/21 بند ٢٩).
| ومدير المؤسسة مالوش أي منها: البريد والتخزين والأمان للنظام كله (docs/24 بند ٦).
*/

/** @return list<string> روابط عناصر السايدبار الظاهرة */
function centralSidebarUrls(): array
{
    return collect(Filament::getPanel('central')->getNavigation())
        ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())->all())
        ->map(fn (NavigationItem $item): ?string => $item->getUrl())
        ->filter()
        ->values()
        ->all();
}

it('بيعرض الإعدادات كعنصر واحد في السايدبار مش ست صفحات', function (): void {
    actingAsCentral('super_admin');

    $urls = centralSidebarUrls();

    expect($urls)->toContain(SettingsCluster::getUrl());

    foreach ([ManageGeneral::class, ManageAppearance::class, ManageStorage::class, ManageMail::class, ManageSecurity::class, ManageNotifications::class] as $page) {
        expect($urls)->not->toContain($page::getUrl());
    }
});

it('الإعدادات العامة في اللوحة المركزية', function (): void {
    actingAsCentral('super_admin');

    expect(ManageGeneral::getUrl())->toEndWith('/central/settings/general')
        ->and(ManageMail::getUrl())->toEndWith('/central/settings/mail');
});

it('رابط الإعدادات بيحوّل لأول تبويب مسموح', function (): void {
    actingAsCentral('super_admin');

    $this->get(SettingsCluster::getUrl())->assertRedirect(ManageGeneral::getUrl());
});

it('بيعرض كل التبويبات للمدير العام', function (): void {
    actingAsCentral('super_admin');

    $response = $this->get(ManageGeneral::getUrl())->assertOk();

    foreach ([ManageAppearance::class, ManageStorage::class, ManageMail::class, ManageSecurity::class, ManageNotifications::class] as $page) {
        $response->assertSee($page::getUrl(), escape: false);
    }
});

it('بيعرض التبويبات المسموحة بس', function (): void {
    actingWithCentralPermissions(['access.panel.central', 'access.system', 'manage_mail.settings']);

    $this->get(SettingsCluster::getUrl())->assertRedirect(ManageMail::getUrl());

    $this->get(ManageMail::getUrl())
        ->assertOk()
        ->assertDontSee(ManageSecurity::getUrl(), escape: false)
        ->assertDontSee(ManageStorage::getUrl(), escape: false);

    $this->get(ManageSecurity::getUrl())->assertForbidden();
});

it('بيرفض رابط الإعدادات لفريق تشغيل مالوش ولا تبويب', function (): void {
    actingAsCentral('support');

    $this->get(SettingsCluster::getUrl())->assertForbidden();
});

it('بيرفض رابط الإعدادات لو معاه تبويب بس من غير access.system', function (): void {
    actingWithCentralPermissions(['access.panel.central', 'manage_mail.settings']);

    $this->get(SettingsCluster::getUrl())->assertForbidden();
});

it('مدير المؤسسة مايقدرش يفتح إعدادات البريد أو التخزين أو الأمان', function (string $page): void {
    actingAsCentral('super_admin');
    $url = $page::getUrl();

    actingAsRole('admin');

    $this->get($url)->assertForbidden();
})->with([ManageMail::class, ManageStorage::class, ManageSecurity::class]);
