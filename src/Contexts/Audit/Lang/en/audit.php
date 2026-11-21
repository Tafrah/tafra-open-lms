<?php

declare(strict_types=1);

return [
    'log' => [
        'singular' => 'Activity record',
        'plural' => 'Activity log',
        'system' => 'System',

        'fields' => [
            'channel' => 'Channel',
            'description' => 'Event',
            'subject' => 'Record',
            'causer' => 'User',
            'ip' => 'IP address',
        ],

        'channels' => [
            'default' => 'General',
            'identity' => 'Identity',
            'security' => 'Security',
            'tenancy' => 'Organizations',
            'content' => 'Content',
            'financial' => 'Financial',
        ],

        'events' => [
            'tenant_provisioned' => 'Organization provisioned',
            'tenant_suspended' => 'Organization suspended',
            'tenant_activated' => 'Organization reactivated',
            'tenant_impersonated' => 'Entered an organization as its admin',
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'force_logout' => 'Sessions terminated',
            'device_terminated' => 'Device session terminated',
            'user_forgotten' => 'User data erased',
            'roles_synced' => 'Roles changed',
            'role_permissions_synced' => 'Role permissions changed',
            'bulk_user_status_changed' => 'Bulk user status change',
            'two_factor_enabled' => 'Two-factor enabled',
            'two_factor_disabled' => 'Two-factor disabled',
            'two_factor_codes_regenerated' => 'Recovery codes regenerated',
            'impersonation_expired' => 'Impersonation expired',
        ],

        'actions' => [
            'details' => 'Details',
        ],

        'empty' => [
            'heading' => 'No activity recorded',
            'description' => 'Every sensitive operation is logged here automatically.',
        ],
    ],

    'health' => [
        'failed_jobs' => [
            'ok' => ':count failed jobs',
            'warning' => 'Failed jobs are piling up: :count',
            'failed' => 'Too many failed jobs: :count',
        ],
        'heading' => 'System check results',
        'last_ran' => 'Last checked :time',
        'never_ran' => 'No check has run yet. Press “Refresh”.',
        'status' => [
            'ok' => 'OK',
            'warning' => 'Warning',
            'failed' => 'Failed',
            'crashed' => 'Crashed',
            'skipped' => 'Skipped',
        ],
    ],

    'logs' => [
        'file' => 'File',
        'no_file' => 'No log files',
        'redacted_notice' => 'Passwords and tokens are stripped before display. This page is read-only.',
    ],

    'backups' => [
        'heading' => 'Backups',
        'description' => 'Backups run automatically every day. A backup whose restore has never been tested is not a backup.',
        'run' => 'Back up now',
        'run_confirm' => 'A full backup will be queued. It may take a while.',
        'queued' => 'Backup queued',
        'empty' => 'No backups yet',
        'fields' => [
            'file' => 'File',
            'disk' => 'Disk',
            'size' => 'Size',
            'date' => 'Date',
        ],
    ],

    'widgets' => [
        'users_description' => 'Total users in this organization',
        'active_description' => 'Active accounts that can sign in',
        'announcements_description' => 'Published announcements',
        'recent_activity' => 'Recent activity',
        'users_chart' => 'User growth — last 12 months',
    ],

    'tours' => [
        'activity' => [
            'purpose' => [
                'title' => 'Activity log',
                'body' => 'Who did what, and when. Every sensitive operation is recorded automatically.',
            ],
            'table' => [
                'title' => 'Append-only',
                'body' => 'Entries cannot be edited or deleted — filter by channel or user to investigate.',
            ],
        ],
        'health' => [
            'purpose' => [
                'title' => 'System health',
                'body' => 'Database, cache, queues, backups and disk — checked on a schedule.',
            ],
            'refresh' => [
                'title' => 'Check now',
                'body' => 'Results come from the last scheduled run. Refresh to check again straight away.',
            ],
            'results' => [
                'title' => 'Read the status',
                'body' => 'A warning needs attention soon; a failure needs it now.',
            ],
        ],
        'backups' => [
            'purpose' => [
                'title' => 'Backups',
                'body' => 'An untested backup is no backup. Check the date of the latest copy regularly.',
            ],
            'run' => [
                'title' => 'Run a backup',
                'body' => 'Starts a backup on the queue — it appears in the list when it finishes.',
            ],
            'list' => [
                'title' => 'Stored copies',
                'body' => 'In database-per-organization mode every organization database is included.',
            ],
        ],
        'logs' => [
            'purpose' => [
                'title' => 'Log viewer',
                'body' => 'Read the application logs without server access.',
            ],
            'viewer' => [
                'title' => 'Pick a file',
                'body' => 'Newest entries come first. Every line carries the request id and the organization id.',
            ],
        ],
    ],
];
