<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\AuthorizationServiceProvider;
use App\Providers\DynamicConfigServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CentralPanelProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ThemeServiceProvider;
use Src\Contexts\Academy\AcademyServiceProvider;
use Src\Contexts\Ai\AiServiceProvider;
use Src\Contexts\Audit\AuditServiceProvider;
use Src\Contexts\Billing\BillingServiceProvider;
use Src\Contexts\Content\ContentServiceProvider;
use Src\Contexts\Exams\ExamsServiceProvider;
use Src\Contexts\Identity\IdentityServiceProvider;
use Src\Contexts\Installer\InstallerServiceProvider;
use Src\Contexts\Media\MediaServiceProvider;
use Src\Contexts\Notifications\NotificationsServiceProvider;
use Src\Contexts\Settings\SettingsServiceProvider;
use Src\Contexts\Students\StudentsServiceProvider;
use Src\Contexts\Transfer\TransferServiceProvider;
use Src\Contexts\Tenancy\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    AuthorizationServiceProvider::class,
    DynamicConfigServiceProvider::class,
    ThemeServiceProvider::class,
    HorizonServiceProvider::class,

    // السياقات — الترتيب هنا هو ترتيب ظهور الموارد في السايدبار
    TenancyServiceProvider::class,
    IdentityServiceProvider::class,
    // المنصة التعليمية
    AcademyServiceProvider::class,
    ExamsServiceProvider::class,
    StudentsServiceProvider::class,
    BillingServiceProvider::class,
    // نقل الأكاديمية من وإلى طفرة (tafra:export / tafra:import)
    TransferServiceProvider::class,
    ContentServiceProvider::class,
    MediaServiceProvider::class,
    NotificationsServiceProvider::class,
    SettingsServiceProvider::class,
    AuditServiceProvider::class,
    AiServiceProvider::class,
    // المثبّت (/install + lms:install) — مالوش شاشات Filament
    InstallerServiceProvider::class,

    // اللوحات آخر حاجة — بتقرا من ContextPanelRegistry
    AdminPanelProvider::class,
    CentralPanelProvider::class,
];
