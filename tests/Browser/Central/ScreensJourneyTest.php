<?php

declare(strict_types=1);

use Src\Contexts\Audit\Presentation\Filament\Central\Pages\Backups;
use Src\Contexts\Audit\Presentation\Filament\Central\Pages\LogViewer;
use Src\Contexts\Audit\Presentation\Filament\Central\Pages\SystemHealth;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource;
use Src\Contexts\Identity\Presentation\Filament\Pages\MyDevices;
use Src\Contexts\Identity\Presentation\Filament\Pages\TwoFactorSettings;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageAppearance;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageGeneral;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageMail;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageNotifications;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageSecurity;
use Src\Contexts\Settings\Presentation\Filament\Central\Pages\ManageStorage;

/*
|--------------------------------------------------------------------------
| رحلات شاشات اللوحة المركزية (docs/17 بند ١٣ · docs/24)
|--------------------------------------------------------------------------
| [الشاشة، مفتاح الجولة، مكان الرابط، دور ممنوع أو null]
| `support` هو الدور الممنوع الطبيعي: بيدخل /central بس مالوش الإعدادات
| ولا النسخ ولا السجلات (docs/24 بند ٧).
*/

dataset('central_screens', [
    'system health' => [SystemHealth::class, 'system.health', 'sidebar', null],
    'backups' => [Backups::class, 'system.backups', 'sidebar', 'support'],
    'log viewer' => [LogViewer::class, 'system.logs', 'sidebar', 'support'],
    'activity log' => [ActivityLogResource::class, 'audit.activity_log', 'sidebar', null],
    'my devices' => [MyDevices::class, 'account.devices', 'user-menu', null],
    'two factor' => [TwoFactorSettings::class, 'account.two_factor', 'user-menu', null],
    'settings general' => [ManageGeneral::class, 'settings.global', 'sidebar', 'support'],
    'settings appearance' => [ManageAppearance::class, 'settings.global', 'sidebar', 'support'],
    'settings mail' => [ManageMail::class, 'settings.global', 'sidebar', 'support'],
    'settings storage' => [ManageStorage::class, 'settings.global', 'sidebar', 'support'],
    'settings security' => [ManageSecurity::class, 'settings.global', 'sidebar', 'support'],
    'settings notifications' => [ManageNotifications::class, 'settings.global', 'sidebar', 'support'],
]);

it('المدير العام بيوصل للشاشة والجولة بتبدأ وبتتقفل', function (string $screen, string $tour, string $area): void {
    actingAsCentral('super_admin');

    $path = screenPath($screen, 'central');
    $nav = navPathFor($screen, 'central');

    // الإعدادات عنصر واحد في السايدبار بيودّي لأول تبويب — والجولة واحدة للتبويبات كلها
    $page = journeyToScreen('/central', $nav, $tour, $area);

    if ($nav !== $path) {
        $page->click(tabLinkSelector($path))->assertPathBeginsWith($path);
    }
})->with('central_screens');

it('الشاشة سليمة على موبايل بالوضع الداكن وبالإنجليزي', function (string $screen): void {
    $user = actingAsCentral('super_admin');
    $user->forceFill(['locale' => 'en'])->save();

    assertMobileDarkQuality(screenPath($screen, 'central'), 'en');
})->with('central_screens');

it('الدور الممنوع مالوش رابط ولا صفحة', function (string $screen, string $tour, string $area, ?string $denied): void {
    if ($denied === null) {
        test()->markTestSkipped('كل أدوار اللوحة ليها الشاشة دي — مفيش دور ممنوع نختبره');
    }

    actingAsCentral($denied);

    assertScreenDenied('/central', screenPath($screen, 'central'), $area, navPathFor($screen, 'central'));
})->with('central_screens');
