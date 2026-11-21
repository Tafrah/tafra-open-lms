<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Src\Support\Infrastructure\Logging\ContextProcessor;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | حقول ممنوع تتكتب في اللوج
    |--------------------------------------------------------------------------
    | ContextProcessor بينقّيها من كل سطر (docs/20 بند ١٣).
    */
    'redact' => [
        'password', 'password_confirmation', 'current_password', 'new_password',
        'token', 'api_key', 'secret', 'authorization', 'cookie',
        'two_factor_secret', 'two_factor_recovery_codes', 'remember_token',
        'credit_card', 'cvv', 'national_id',
    ],

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'daily')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => (int) env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // القناة الأساسية في الإنتاج — JSON مهيكل
        'json' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'info'),
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/app.json'),
                'maxFiles' => 30,
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [
                ContextProcessor::class,
                [
                    'processor' => PsrLogMessageProcessor::class,
                    'with' => ['removeUsedContextFields' => true],
                ],
            ],
        ],

        // للحاويات — stdout عشان جامع اللوجات يلمّه
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'info'),
            'handler' => StreamHandler::class,
            'handler_with' => ['stream' => 'php://stderr'],
            'formatter' => JsonFormatter::class,
            'processors' => [ContextProcessor::class],
        ],

        // قناة تدقيق منفصلة — الاحتفاظ أطول بكتير
        'audit' => [
            'driver' => 'monolog',
            'level' => 'info',
            'handler' => RotatingFileHandler::class,
            'handler_with' => [
                'filename' => storage_path('logs/audit.json'),
                'maxFiles' => 365,
            ],
            'formatter' => JsonFormatter::class,
            'processors' => [ContextProcessor::class],
        ],

        'sentry' => [
            'driver' => 'sentry',
            'level' => env('LOG_LEVEL', 'error'),
            'bubble' => true,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],
    ],
];
