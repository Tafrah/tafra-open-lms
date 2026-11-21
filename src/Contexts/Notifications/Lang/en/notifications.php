<?php

declare(strict_types=1);

return [
    'menu' => [
        'preferences' => 'Notification preferences',
    ],

    'preferences' => [
        'title' => 'Notification preferences',
        'description' => 'Choose how each kind of notification reaches you. Required channels are locked for security reasons.',
        'saved' => 'Preferences saved',
        'required' => 'Required',
        'required_hint' => 'This is a security notification — it cannot be turned off.',
    ],

    'channels' => [
        'database' => 'In panel',
        'mail' => 'Email',
        'broadcast' => 'Real time',
    ],

    'groups' => [
        'identity' => 'Account',
        'security' => 'Security',
        'system' => 'System',
        'content' => 'Content',
        'reports' => 'Reports',
    ],

    'catalog' => [
        'user_invited' => [
            'label' => 'New user invited',
            'description' => 'When someone is invited to the organization.',
        ],
        'password_changed' => [
            'label' => 'Password changed',
            'description' => 'Security alert when your account password changes.',
        ],
        'new_device_login' => [
            'label' => 'Sign-in from a new device',
            'description' => 'Security alert the first time a new device signs in.',
        ],
        'export_ready' => [
            'label' => 'Export ready',
            'description' => 'When an export you requested finishes.',
        ],
        'announcement_published' => [
            'label' => 'New announcement',
            'description' => 'When an announcement is published to the organization.',
        ],
        'weekly_digest' => [
            'label' => 'Weekly digest',
            'description' => 'A weekly summary of organization activity.',
        ],
    ],

    'export' => [
        'subject' => 'Your export is ready',
        'body' => 'The :resource export finished — :rows rows.',
        'expiry' => 'The link is valid for a limited time, so download it soon.',
    ],

    'digest' => [
        'subject' => 'Weekly digest — :organization',
        'intro' => 'Here is what happened in your organization last week:',
        'stats' => [
            'total' => 'Total users: :count',
            'joined' => 'New users: :count',
            'invited' => 'Invitations not accepted yet: :count',
            'published' => 'Announcements published: :count',
            'drafts' => 'Draft announcements: :count',
        ],
    ],

    'tours' => [
        'preferences' => [
            'purpose' => [
                'title' => 'Choose what reaches you',
                'body' => 'Pick which notifications you receive and on which channel.',
            ],
            'matrix' => [
                'title' => 'Required notifications',
                'body' => 'Security notifications cannot be turned off — they always reach you.',
            ],
        ],
    ],
];
