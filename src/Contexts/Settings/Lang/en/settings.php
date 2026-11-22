<?php

declare(strict_types=1);

return [
    'cluster' => 'Settings',

    'pages' => [
        'general' => 'General settings',
        'appearance' => 'Appearance',
        'storage' => 'Storage',
        'mail' => 'Mail',
        'security' => 'Security',
        'notifications' => 'Notifications',
        'ai' => 'AI access',
    ],

    'sections' => [
        'identity' => 'Application identity',
        'identity_help' => 'Name, description and contact details — shown to users in the panel and in emails.',
        'locale' => 'Language & time',
        'maintenance' => 'Maintenance mode',
        'maintenance_help' => 'While enabled, sensitive operations are blocked and a warning bar appears at the top of the panel.',
        'brand' => 'Brand',
        'brand_help' => 'You set one colour — the full scale is derived from it automatically using OKLCH.',
        'layout' => 'Layout',
        'footer' => 'Footer',
        'disks' => 'Disks',
        'disks_help' => 'The code never knows about s3 or local — it asks the DiskResolver, which reads from here.',
        'collections' => 'Collections',
        'limits' => 'Limits',
        'smtp' => 'Mail server',
        'sender' => 'Sender',
        'passwords' => 'Passwords',
        'two_factor' => 'Two-factor authentication',
        'sessions' => 'Sessions',
        'channels' => 'Channels',
        'channels_help' => 'Turning a channel off here disables it system-wide — user preferences cannot override it.',
        'retention' => 'Retention',
        'ai' => 'AI access (MCP)',
    ],

    'fields' => [
        'app_name' => 'Application name',
        'app_description' => 'Application description',
        'support_email' => 'Support email',
        'support_phone' => 'Support phone',
        'default_locale' => 'Default language',
        'timezone' => 'Timezone',
        'maintenance_mode' => 'Enable maintenance mode',
        'maintenance_message' => 'Maintenance message',
        'primary_color' => 'Primary colour',
        'font' => 'Font',
        'logo_light' => 'Logo (light)',
        'logo_dark' => 'Logo (dark)',
        'favicon' => 'Favicon',
        'default_theme' => 'Default theme',
        'allow_theme_switch' => 'Allow theme switching',
        'sidebar_default' => 'Sidebar state',
        'footer_text' => 'Copyright text',
        'default_disk' => 'Default disk',
        'private_disk' => 'Private files disk',
        'conversions_disk' => 'Conversions disk',
        'private_collections' => 'Private collections',
        'collection_disks' => 'Disk per collection',
        'collection' => 'Collection',
        'disk' => 'Disk',
        'max_upload_size' => 'Maximum upload size',
        'allowed_mimes' => 'Allowed types',
        'mail_driver' => 'Driver',
        'mail_host' => 'Host',
        'mail_port' => 'Port',
        'mail_encryption' => 'Encryption',
        'mail_username' => 'Username',
        'mail_password' => 'Password',
        'mail_from_address' => 'From address',
        'mail_from_name' => 'From name',
        'password_min_length' => 'Minimum password length',
        'password_uncompromised' => 'Reject breached passwords',
        'password_history' => 'Block reuse of the last',
        'password_expiry' => 'Password expiry (days)',
        'two_factor_roles' => 'Roles required to use two-factor',
        'two_factor_grace' => 'Grace period (days)',
        'session_lifetime' => 'Session lifetime (minutes)',
        'impersonation_max' => 'Maximum impersonation (minutes)',
        'mcp_enabled' => 'Allow AI apps to connect (Claude, ChatGPT)',
        'mcp_allow_writes' => 'Allow AI apps to make changes',
        'prune_read_after' => 'Delete read notifications after',
        'digest_hour' => 'Daily digest hour',
    ],

    'help' => [
        'primary_color' => 'Shade 600 only. Shades 600 and 700 must pass a 4.5:1 contrast ratio on white.',
        'private_disk' => 'Private files are served through temporary links only — never a permanent URL.',
        'private_collections' => 'Any collection listed here goes to the private disk and is served with temporary links.',
        'collection_disks' => 'An explicit override for one collection — it wins over the default.',
        'allowed_mimes' => 'An allow-list. Anything not listed is rejected on the server, not just in the browser.',
        'mail_password' => 'Stored encrypted in the database.',
        'password_uncompromised' => 'Checks against known breach databases — production only.',
        'password_expiry' => 'Zero means passwords never expire.',
        'impersonation_max' => 'After this long, the impersonated session ends automatically.',
        'mcp_enabled' => 'Turning this off cuts every connected AI app off immediately, across all organizations — no deploy needed.',
        'mcp_tenant' => 'AI apps act as the person who connected them, with that person’s permissions — never more. Turning access off cuts every connection in this organization immediately.',
        'mcp_allow_writes' => 'When off, every connection in this organization is read-only, even if a member chose read & write.',
    ],

    'themes' => [
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'Follow system',
    ],

    'sidebar' => [
        'expanded' => 'Expanded',
        'collapsed' => 'Collapsed',
    ],

    'units' => [
        'days' => 'days',
    ],

    'actions' => [
        'test_connection' => 'Test connection',
        'send_test_mail' => 'Send a test email',
    ],

    'validation' => [
        'low_contrast' => 'This colour has a contrast ratio below 4.5:1 on white — text on it will not be readable. Pick a darker shade.',
    ],

    'notifications' => [
        'storage_ok' => 'Connection to disk :disk is working',
        'storage_failed' => 'Could not connect to disk :disk',
        'mail_sent' => 'Test email sent',
        'mail_failed' => 'Could not send the test email',
        'test_mail_subject' => 'Test email from your admin panel',
        'test_mail_body' => 'If this message reached you, the mail settings are working.',
    ],

    'tenant' => [
        'cluster' => 'Organization settings',
        'inherits' => 'These apply to this organization only. Mail, storage and security are platform-wide and managed by the operations team.',
    ],

    'saved' => 'Settings saved',
    'saved_body' => 'The changes are live. If you do not see them, refresh the page.',

    'tours' => [
        'global' => [
            'tabs' => [
                'title' => 'One place for platform settings',
                'body' => 'Each tab is a settings group. You only see the tabs you are allowed to change.',
            ],
            'form' => [
                'title' => 'Platform-wide',
                'body' => 'These values apply to every organization, unless an organization overrides its own appearance or notifications.',
            ],
            'save' => [
                'title' => 'Save',
                'body' => 'Changes apply immediately — no deployment needed.',
            ],
        ],
        'organization' => [
            'tabs' => [
                'title' => 'Your organization settings',
                'body' => 'Appearance and notifications for this organization only.',
            ],
            'form' => [
                'title' => 'Inherited values',
                'body' => 'Fields start with the platform default. Change only what should be different.',
            ],
            'save' => [
                'title' => 'Save',
                'body' => 'Changes apply to your organization members straight away.',
            ],
        ],
    ],
    'menu' => [
        'platform' => 'Platform settings (email)',
    ],
];
