<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | المثبّت — تثبيت بخطوة واحدة (docs/install)
    |--------------------------------------------------------------------------
    | الاستضافة المشتركة (cPanel / Hostinger): مفيش SSH ولا Redis ولا عامل
    | طابور. المثبّت على `/install` بيكتب `.env` ويجهّز القاعدة ويعمل المدير،
    | و`php artisan lms:install` بيعمل نفس الخطوات من غير واجهة (VPS / Docker).
    */

    // الاختبارات بتعتبر التطبيق متثبّت — `phpunit.xml` بيضبطها، واختبارات
    // المثبّت نفسه بتقفلها صراحةً
    'assume_installed' => (bool) env('INSTALLER_ASSUME_INSTALLED', false),

    // علامة «اتثبّت» — آخر خطوة في التثبيت بتكتبها
    'marker_path' => storage_path('app/installed.json'),

    // قفل عشان طلبين تثبيت في نفس الوقت مايشتغلوش على بعض
    'lock_path' => storage_path('framework/installer.lock'),

    'requirements' => [
        'php' => '8.4.0',

        'extensions' => [
            'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json',
            'bcmath', 'fileinfo', 'intl', 'zip', 'curl',
        ],

        // كفاية واحد من كل مجموعة
        'one_of' => [
            'database' => ['pdo_mysql', 'pdo_pgsql'],
            'images' => ['gd', 'imagick'],
        ],

        // مجلدات لازم تكون قابلة للكتابة — نسبية لجذر المشروع
        'writable' => ['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'],
    ],

    'database' => [
        'drivers' => ['mysql', 'pgsql'],
        'default_ports' => ['mysql' => 3306, 'pgsql' => 5432],
        // JSON + utf8mb4 — أقل من كده الميجريشنز بتفشل
        'min_versions' => ['mysql' => '5.7.8', 'mariadb' => '10.3.0', 'pgsql' => '13.0'],
        'connect_timeout' => 5,
    ],

    // المؤسسة الوحيدة (وضع single) والدور اللي صاحب المنصة بياخده فيها.
    // صاحب المنصة كمان `super_admin` على الفريق المركزي — هو مالك التثبيت كله
    'tenant' => [
        'fallback_slug' => 'academy',
        'primary_color' => '#419DF1',
        'owner_role' => 'admin',
    ],

    /*
    | عامل الطابور من الجدولة: `queue:work --stop-when-empty` كل دقيقة.
    | على الاستضافة المشتركة ده العامل الوحيد. على Docker/VPS اللي فيه عامل
    | دايم (supervisor / Horizon) اقفله بـ QUEUE_WORKER_VIA_SCHEDULER=false.
    */
    'scheduled_worker' => [
        'enabled' => (bool) env('QUEUE_WORKER_VIA_SCHEDULER', true),
        'queues' => 'critical,notifications,media,exports,default',
        'max_time' => 50,
        'tries' => 3,
    ],

    // ملفات الديسك العام من غير symlink (استضافة قافلة `symlink()`):
    // التطبيق بيقدّمها بنفسه من المسار ده (`PUBLIC_STORAGE_URL`)
    'public_files' => [
        'disk' => 'public',
        'prefix' => 'uploads',
        'max_age' => 604800,
    ],

    /*
    | فحص الإصدارات الجديدة: مرة في اليوم من الجدولة، طلب GET واحد لـ GitHub
    | (مفيش أي بيانات عن المنصة بتتبعت). النتيجة بتظهر كشريط في لوحة التحكم
    | للمدير بس. اقفله بـ LMS_UPDATE_CHECK=false.
    */
    'updates' => [
        'enabled' => (bool) env('LMS_UPDATE_CHECK', true),
        'url' => env('LMS_UPDATE_URL', 'https://api.github.com/repos/Tafrah/tafra-open-lms/releases/latest'),
        'guide' => 'https://github.com/Tafrah/tafra-open-lms/blob/main/docs/install/shared-hosting.md#التحديث',
    ],

    // ثابت من GitHub Actions في ملف `VERSION` جوه الـ zip — وإلا من APP_VERSION
    'version_file' => base_path('VERSION'),
];
