<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | الديسك الافتراضي
    |--------------------------------------------------------------------------
    | ملاحظة: الكود مابيسألش عن الديسك ده مباشرة — بيسأل DiskResolver
    | اللي بيقرا من الإعدادات وقت التشغيل (docs/04-media-filesystem.md).
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => true,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        // الملفات العامة (لوجوهات، صور الملف الشخصي)
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

        // الملفات الخاصة (عقود، مستندات، هويات) — روابط مؤقتة فقط
        's3-private' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_PRIVATE_BUCKET', env('AWS_BUCKET')),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private',
            'throw' => true,
            'report' => false,
        ],

        // النسخ الاحتياطي
        's3-backups' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BACKUP_BUCKET', env('AWS_BUCKET')),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'private',
            'throw' => true,
            'report' => false,
        ],

        'backups-local' => [
            'driver' => 'local',
            'root' => storage_path('backups'),
            'throw' => true,
            'report' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
