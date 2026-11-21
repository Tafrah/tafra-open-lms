<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\AuthorizationServiceProvider;
use App\Providers\DynamicConfigServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\CentralPanelProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ThemeServiceProvider;
use Src\Contexts\Ai\AiServiceProvider;
use Src\Contexts\Audit\AuditServiceProvider;
use Src\Contexts\Content\ContentServiceProvider;
use Src\Contexts\Identity\IdentityServiceProvider;
use Src\Contexts\Media\MediaServiceProvider;
use Src\Contexts\Notifications\NotificationsServiceProvider;
use Src\Contexts\Settings\SettingsServiceProvider;
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
    ContentServiceProvider::class,
    MediaServiceProvider::class,
    NotificationsServiceProvider::class,
    SettingsServiceProvider::class,
    AuditServiceProvider::class,
    AiServiceProvider::class,

    // اللوحات آخر حاجة — بتقرا من ContextPanelRegistry
    AdminPanelProvider::class,
    CentralPanelProvider::class,
];
