<?php

declare(strict_types=1);

return [

    'user' => [

        'forgotten' => 'Deleted user',
        'singular' => 'User',
        'plural' => 'Users',

        'sections' => [
            'identity' => 'User details',
            'identity_help' => 'Name, email and contact details.',
            'access' => 'Access & permissions',
            'access_help' => 'Status, role and password. Roles apply within the current organization only.',
        ],

        'fields' => [
            'code' => 'Code',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'password' => 'Password',
            'avatar' => 'Avatar',
            'roles' => 'Roles',
            'is_active' => 'Account enabled',
            'last_login' => 'Last login',
            'tenants' => 'Organizations',
        ],

        'help' => [
            'phone' => 'International format, e.g. +201000000000',
            'password' => 'Leave blank to keep the current password.',
            'roles' => 'Roles apply inside the current organization — the same user can hold a different role elsewhere.',
        ],

        'status' => [
            'invited' => 'Invited',
            'active' => 'Active',
            'suspended' => 'Suspended',
        ],

        'actions' => [
            'create' => 'Add user',
            'impersonate' => 'Impersonate',
            'force_logout' => 'Terminate sessions',
            'bulk_activate' => 'Activate selected',
            'bulk_suspend' => 'Suspend selected',
        ],

        'confirm' => [
            'impersonate_heading' => 'Sign in as this user?',
            'impersonate_body' => 'You will see the panel exactly as they do. This is logged and ends automatically after the configured period.',
            'force_logout_heading' => 'Terminate all sessions for this user?',
            'force_logout_body' => 'They will be signed out of every device immediately.',
        ],

        'notifications' => [
            'invited' => [
                'subject' => 'You have been invited',
                'short' => 'You have been invited to join :organization.',
                'body' => 'You have been invited to join :organization on the admin panel. Use the button below to set your password and sign in.',
                'cta' => 'Accept the invitation',
                'expiry' => 'This link expires after a while — if it does, ask an admin to send a new invitation.',
            ],
            'logged_out' => 'Sessions terminated',
            'roles_updated' => 'Roles updated',
        ],

        'badges' => [
            'invited' => 'Invited users who have not signed in yet',
        ],

        'empty' => [
            'heading' => 'No users yet',
            'description' => 'Start by adding the first user to this organization and set their role.',
            'cta' => 'Add the first user',
        ],

        'export' => [
            'completed' => '{0} No rows exported|{1} One row exported|[2,*] :count rows exported',
        ],
    ],

    'device' => [
        'singular' => 'Device',
        'plural' => 'My devices',
        'heading' => 'Active devices',
        'description' => 'These are the devices you have signed in from. If one is not yours, terminate its session and change your password right away.',

        'fields' => [
            'device' => 'Device',
            'platform' => 'Platform',
            'browser' => 'Browser',
            'ip' => 'IP address',
            'last_active' => 'Last active',
            'current' => 'Current device',
        ],

        'actions' => [
            'terminate' => 'Terminate session',
            'terminate_others' => 'Terminate all other sessions',
        ],

        'confirm' => [
            'terminate_heading' => 'Terminate this device session?',
            'terminate_body' => 'The device will be signed out immediately.',
        ],

        'notifications' => [
            'new_device_subject' => 'Sign-in from a new device',
            'new_device_body' => 'Your account was signed in from :device — IP :ip.',
            'new_device_action' => 'If this was not you, change your password now and terminate the sessions from “My devices”.',
            'terminated' => 'Session terminated',
            'others_terminated' => 'All other sessions terminated',
        ],

        'empty' => [
            'heading' => 'No devices recorded',
            'description' => 'Devices appear here the first time you sign in from them.',
        ],
    ],

    'two_factor' => [
        'title' => 'Two-factor authentication',
        'description' => 'A second layer of protection: after your password, the panel asks for a code from your authenticator app.',
        'enabled' => 'Two-factor authentication is enabled',
        'disabled' => 'Two-factor authentication is not enabled',
        'required_title' => 'Two-factor authentication required',
        'required_body' => 'Your role requires two-factor authentication and the grace period has ended. Enable it to continue.',
        'scan_heading' => 'Scan this code with your authenticator app',
        'scan_body' => 'Use Google Authenticator or any TOTP app, then enter the code it shows.',
        'setup_key' => 'Setup key',
        'recovery_heading' => 'Recovery codes',
        'recovery_body' => 'Store these somewhere safe. Each code works once, and you will not see them again.',

        'fields' => [
            'code' => 'Verification code',
            'password' => 'Current password',
        ],

        'actions' => [
            'enable' => 'Enable',
            'confirm' => 'Confirm',
            'disable' => 'Disable',
            'regenerate' => 'Regenerate codes',
            'download_codes' => 'Download codes',
        ],

        'notifications' => [
            'enabled' => 'Two-factor authentication enabled',
            'disabled' => 'Two-factor authentication disabled',
            'regenerated' => 'New recovery codes generated',
            'invalid_code' => 'That code is not valid',
        ],
    ],

    'role' => [
        'singular' => 'Role',
        'plural' => 'Roles',

        'sections' => [
            'identity' => 'Role details',
            'permissions' => 'Permissions',
            'permissions_help' => 'Permissions come from the config file. This screen only maps them to roles.',
        ],

        'fields' => [
            'name' => 'Role name',
            'label' => 'Display name',
            'permissions' => 'Permissions',
            'users_count' => 'Users',
            'protected' => 'Protected',
            'permissions_count' => 'Permissions',
        ],

        'actions' => [
            'select_all' => 'Select all',
        ],

        'empty' => [
            'heading' => 'No roles',
            'description' => 'Run `php artisan authorization:sync` to generate them from config.',
        ],
    ],

    'password' => [
        'notifications' => [
            'changed_subject' => 'Your password changed',
            'changed_body' => 'The password on your account was just changed.',
            'changed_action' => 'If you did not do this, contact support immediately.',
        ],
        'validation' => [
            'recently_used' => 'That password was used within your last :count passwords — pick a new one.',
        ],
    ],

    'tours' => [
        'roles' => [
            'purpose' => [
                'title' => 'Roles decide what people can do',
                'body' => 'A role is a set of permissions. Give a user a role instead of granting permissions one by one.',
            ],
            'table' => [
                'title' => 'Protected roles',
                'body' => 'Protected roles cannot be edited or deleted — they stop the organization from locking itself out.',
            ],
            'create' => [
                'title' => 'New role',
                'body' => 'Create a role and tick the permissions it grants, grouped by area.',
            ],
        ],
        'two_factor' => [
            'purpose' => [
                'title' => 'Protect your account',
                'body' => 'Two-factor authentication asks for a code from your phone on top of your password.',
            ],
            'setup' => [
                'title' => 'Scan and confirm',
                'body' => 'Scan the QR code with an authenticator app, then type the code to confirm. Keep the recovery codes somewhere safe.',
            ],
        ],
        'devices' => [
            'purpose' => [
                'title' => 'Where you are signed in',
                'body' => 'Every browser and device with an active session on your account.',
            ],
            'table' => [
                'title' => 'Sign a device out',
                'body' => 'Do not recognise a device? End its session here, then change your password.',
            ],
        ],
        'users' => [
            'search' => [
                'title' => 'Quick search',
                'body' => 'Type a name, email or code — search runs when you leave the field so it does not query on every keystroke.',
            ],
            'filters' => [
                'title' => 'Filters',
                'body' => 'Filters persist when you come back, and every active one shows as an indicator above the table.',
            ],
            'columns' => [
                'title' => 'Columns',
                'body' => 'Hide or reorder columns — your choice is saved for you alone.',
            ],
            'create' => [
                'title' => 'Add a user',
                'body' => 'This is where you add a user and set their role inside the current organization.',
            ],
        ],
    ],

    'menu' => [
        'devices' => 'My devices',
        'two_factor' => 'Two-factor authentication',
    ],
];
