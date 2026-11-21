<?php

declare(strict_types=1);

use Src\Contexts\Ai\Presentation\Filament\Pages\AiConnections;
use Src\Contexts\Audit\Presentation\Filament\Resources\ActivityLogResource;
use Src\Contexts\Identity\Presentation\Filament\Pages\MyDevices;
use Src\Contexts\Identity\Presentation\Filament\Pages\TwoFactorSettings;
use Src\Contexts\Identity\Presentation\Filament\Resources\RoleResource;
use Src\Contexts\Identity\Presentation\Filament\Resources\UserResource;
use Src\Contexts\Media\Presentation\Filament\Resources\MediaResource;
use Src\Contexts\Notifications\Presentation\Filament\Pages\NotificationPreferences;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantAi;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantAppearance;
use Src\Contexts\Settings\Presentation\Filament\Pages\ManageTenantNotifications;
use Src\Contexts\Storefront\Presentation\Filament\Pages\ManageSiteSettings;
use Src\Contexts\Tenancy\Presentation\Filament\Pages\EditOrganization;

/*
|--------------------------------------------------------------------------
| رحلات شاشات لوحة المؤسسة (docs/17 بند ١٣)
|--------------------------------------------------------------------------
| الشاشات اللي من غير CRUD خاص بيها — نفس الرحلة لكل واحدة. الشاشات اللي
| فيها إنشاء وتعديل ليها ملف رحلة لوحدها (AnnouncementJourneyTest).
|
| [الشاشة، مفتاح الجولة، مكان الرابط، دور ممنوع أو null]
*/

dataset('admin_screens', [
    'users' => [UserResource::class, 'users.list', 'sidebar', null],
    'organization profile' => [EditOrganization::class, 'tenancy.organization_profile', 'user-menu', 'viewer'],
    'roles' => [RoleResource::class, 'roles.list', 'sidebar', 'editor'],
    'media' => [MediaResource::class, 'media.library', 'sidebar', null],
    'activity log' => [ActivityLogResource::class, 'audit.activity_log', 'sidebar', 'editor'],
    'my devices' => [MyDevices::class, 'account.devices', 'user-menu', null],
    'two factor' => [TwoFactorSettings::class, 'account.two_factor', 'user-menu', null],
    'notification preferences' => [NotificationPreferences::class, 'notifications.preferences', 'user-menu', null],
    'organization appearance' => [ManageTenantAppearance::class, 'settings.organization', 'sidebar', 'editor'],
    'organization notifications' => [ManageTenantNotifications::class, 'settings.organization', 'sidebar', 'editor'],
    'organization ai access' => [ManageTenantAi::class, 'settings.organization', 'sidebar', 'editor'],
    'site settings' => [ManageSiteSettings::class, 'storefront.site_settings', 'sidebar', 'editor'],
    'connected ai apps' => [AiConnections::class, 'ai.connections', 'user-menu', null],
]);

it('المدير بيوصل للشاشة والجولة بتبدأ وبتتقفل', function (string $screen, string $tour, string $area): void {
    actingAsRole('admin');

    $path = screenPath($screen);
    $nav = navPathFor($screen);

    // تبويبات الإعدادات: السايدبار فيه الإعدادات كعنصر واحد بيودّي لأول تبويب
    $page = journeyToScreen(adminHome(), $nav, $tour, $area);

    if ($nav !== $path) {
        $page->click(tabLinkSelector($path))->assertPathBeginsWith($path);
    }
})->with('admin_screens');

it('الشاشة سليمة على موبايل بالوضع الداكن وبالإنجليزي', function (string $screen): void {
    $user = actingAsRole('admin');
    $user->forceFill(['locale' => 'en'])->save();

    assertMobileDarkQuality(screenPath($screen), 'en');
})->with('admin_screens');

it('الدور الممنوع مالوش رابط ولا صفحة', function (string $screen, string $tour, string $area, ?string $denied): void {
    if ($denied === null) {
        test()->markTestSkipped('كل أدوار اللوحة ليها الشاشة دي — مفيش دور ممنوع نختبره');
    }

    actingAsRole($denied);

    assertScreenDenied(adminHome(), screenPath($screen), $area, navPathFor($screen));
})->with('admin_screens');
