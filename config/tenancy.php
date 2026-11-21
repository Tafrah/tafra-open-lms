<?php

declare(strict_types=1);

use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Contexts\Tenancy\Infrastructure\Seeders\TenantDefaultsSeeder;
use Src\Support\Infrastructure\Tenancy\TenantContextBootstrapper;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;
use Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager;

/*
|--------------------------------------------------------------------------
| وضع تعدد المستأجرين (docs/03)
|--------------------------------------------------------------------------
| single : قاعدة واحدة + عمود tenant_id (الافتراضي)
| multi  : قاعدة بيانات لكل مستأجر + قاعدة مركزية
|
| الوضع بيتقفل عند أول نشر بـ `php artisan tenancy:init --mode=...`.
| لو `TENANCY_MODE` اتغيّر بعدها، التطبيق بيرفض يشتغل — خطأ في .env
| مايبقاش «مؤسسات فاضية» أو تسريب لكل الصفوف (docs/21 بند ٣٠).
*/
$mode = env('TENANCY_MODE', 'single');

return [

    'mode' => $mode,

    'modes' => ['single', 'multi'],

    /*
    | الجدول المركزي اللي بيتسجّل فيه الوضع المقفول.
    */
    'lock' => [
        'table' => 'system_state',
        'key' => 'tenancy.mode',
    ],

    'tenant_model' => Tenant::class,

    // مكان رجوع فريق التشغيل بعد ما ينهي انتحال مدير مؤسسة (docs/24 بند ٤)
    'central_panel_url' => '/central',

    // null = مفاتيح أرقام متسلسلة (مش UUID) — نفس الجدول الحالي
    'id_generator' => null,

    'domain_model' => Domain::class,

    // التعرّف على المستأجر بيحصل من مسار Filament (`/admin/t/{slug}`) مش من الدومين
    'central_domains' => [],

    /*
    | الـ bootstrappers بتتحدد من الوضع:
    | في single مفيش تبديل قاعدة — بس الصلاحيات والكاش واللوج والطابور.
    | CacheTenancyBootstrapper مش مستخدم: محتاج store بيدعم tags،
    | والكاش عندنا على `database` — العزل بالبادئة في TenantContextBootstrapper.
    */
    'bootstrappers' => array_values(array_filter([
        $mode === 'multi' ? DatabaseTenancyBootstrapper::class : null,
        TenantContextBootstrapper::class,
        QueueTenancyBootstrapper::class,
    ])),

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),

        'template_tenant_connection' => null,

        'prefix' => env('TENANCY_DB_PREFIX', 'fc_tenant_'),
        'suffix' => '',

        'managers' => [
            'sqlite' => SQLiteDatabaseManager::class,
            'mysql' => MySQLDatabaseManager::class,
            'mariadb' => MySQLDatabaseManager::class,
            'pgsql' => PostgreSQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [],
        'root_override' => [],
        'suffix_storage_path' => false,
        'asset_helper_tenancy' => false,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [],

    // مسارات stancl الجاهزة (asset route) مش محتاجينها
    'routes' => false,

    /*
    | `--path` بيتملى وقت التشغيل من ContextServiceProvider: كل سياق
    | بيضيف `Database/Migrations/Tenant` بتاعه.
    */
    'migration_parameters' => [
        '--force' => true,
        '--path' => [],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => TenantDefaultsSeeder::class,
        '--force' => true,
    ],

    /*
    | إنشاء قاعدة المستأجر وتهجيرها — في الطابور في الإنتاج.
    */
    'provisioning' => [
        'queued' => (bool) env('TENANCY_PROVISION_QUEUED', false),
    ],
];
