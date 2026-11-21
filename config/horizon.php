<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', Str::slug((string) env('APP_NAME', 'fc-admin'), '_').'_horizon:'),

    'middleware' => ['web'],

    'waits' => [
        'redis:critical' => 30,
        'redis:notifications' => 60,
        'redis:media' => 120,
        'redis:exports' => 300,
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => ['job' => 24, 'queue' => 24],
    ],

    'fast_termination' => false,

    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | الطوابير وأولوياتها (docs/13 بند ١)
    |--------------------------------------------------------------------------
    | critical(١) → notifications(٢) → media(٣) → exports(٤) → default(٥)
    |
    | كل طابور له supervisor مستقل عشان تصدير تقيل ما يقعّدش إشعار أمني
    | ورا في الصف.
    */
    'defaults' => [
        'supervisor-critical' => [
            'connection' => 'redis',
            'queue' => ['critical'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],

        'supervisor-notifications' => [
            'connection' => 'redis',
            'queue' => ['notifications'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 5,
            'timeout' => 120,
            'nice' => 0,
        ],

        'supervisor-media' => [
            'connection' => 'redis',
            'queue' => ['media'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            // التحويلات بتاكل ذاكرة ووقت أكتر من أي حاجة تانية
            'memory' => 512,
            'tries' => 3,
            'timeout' => 600,
            'nice' => 0,
        ],

        'supervisor-exports' => [
            'connection' => 'redis',
            'queue' => ['exports'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 300,
            'nice' => 0,
        ],

        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 300,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-critical' => ['minProcesses' => 2, 'maxProcesses' => 10],
            'supervisor-notifications' => ['minProcesses' => 2, 'maxProcesses' => 8],
            'supervisor-media' => ['minProcesses' => 1, 'maxProcesses' => 6],
            'supervisor-exports' => ['minProcesses' => 1, 'maxProcesses' => 4],
            'supervisor-default' => ['minProcesses' => 1, 'maxProcesses' => 6],
        ],

        'staging' => [
            'supervisor-critical' => ['maxProcesses' => 3],
            'supervisor-notifications' => ['maxProcesses' => 3],
            'supervisor-media' => ['maxProcesses' => 2],
            'supervisor-exports' => ['maxProcesses' => 2],
            'supervisor-default' => ['maxProcesses' => 2],
        ],

        'local' => [
            'supervisor-local' => [
                'connection' => 'redis',
                'queue' => ['critical', 'notifications', 'media', 'exports', 'default'],
                'balance' => 'auto',
                'maxProcesses' => 3,
                'memory' => 256,
                'tries' => 3,
                'timeout' => 300,
            ],
        ],
    ],
];
