<?php

declare(strict_types=1);

return [
    'brand' => 'Tafra — the teacher platform',
    'title' => 'Install the platform',
    'switch_language' => 'العربية',
    'footer' => 'A free, open-source learning platform for teachers.',
    'continue' => 'Continue',
    'back' => 'Back',
    'technical_details' => 'Technical details (send these to support if the problem persists)',

    'nav' => [
        'requirements' => 'Requirements',
        'database' => 'Database',
        'academy' => 'Platform & admin',
        'finished' => 'Done',
    ],

    'requirements' => [
        'title' => 'Hosting check',
        'lead' => 'We make sure your hosting has everything the platform needs before we start.',
        'php_version' => 'PHP :version or newer',
        'memory' => 'PHP memory (memory_limit) of at least :size',
        'blocked' => 'Some requirements are missing. Fix them from your hosting panel (for example "Select PHP Version" in cPanel), then check again.',
        'recheck' => 'Check again',
        'groups' => [
            'php' => 'PHP',
            'extensions' => 'PHP extensions',
            'permissions' => 'Write permissions',
            'recommended' => 'Recommended (optional)',
        ],
        'hints' => [
            'php_version' => 'Switch this domain to PHP 8.4 from your hosting panel.',
            'extension' => 'Enable this extension from "Select PHP Version" → Extensions.',
            'one_of' => [
                'database' => 'We need pdo_mysql (or pdo_pgsql if you use PostgreSQL).',
                'images' => 'We need gd or imagick for student and content images.',
            ],
            'writable' => 'Set this folder to 775 from the file manager.',
            'env' => 'The installer needs to write a .env file in the project folder — set the folder to 755, or create an empty .env file with 644.',
            'symlink' => 'Your host disables symlink() — that is fine: the platform serves images and files itself from /uploads.',
            'proc_open' => 'Scheduled tasks (cron) run their commands with proc_open(). It is disabled here (the web PHP) — the command-line PHP usually still allows it and everything will work, but if notifications and image processing do not run after you add the cron job, ask your host to enable it.',
            'memory' => 'The platform may need more memory while processing images. Raise memory_limit to 256M if you can.',
        ],
    ],

    'database' => [
        'title' => 'Database',
        'lead' => 'Create a database and a user from your hosting panel, then enter their details here.',
        'cpanel_hint' => 'In cPanel: "MySQL Database Wizard" → create the database and the user → grant ALL PRIVILEGES. Names usually start with your account name, like youruser_lms. The host is usually localhost.',
        'submit' => 'Test the connection and continue',
        'drivers' => [
            'mysql' => 'MySQL / MariaDB',
            'pgsql' => 'PostgreSQL',
        ],
        'fields' => [
            'db_driver' => 'Database type',
            'db_host' => 'Host',
            'db_port' => 'Port',
            'db_database' => 'Database name',
            'db_username' => 'Username',
            'db_password' => 'Password',
        ],
        'errors' => [
            'access_denied' => 'Wrong username or password. Check them in your hosting panel (including the prefix before the username).',
            'no_database_access' => 'This user has no access to this database. In cPanel, add the user to the database with ALL PRIVILEGES.',
            'unknown_database' => 'No database with this name. Use the full name with its prefix (like youruser_lms).',
            'no_create_privilege' => 'The user can connect but cannot create tables. Grant it ALL PRIVILEGES on the database.',
            'unreachable' => 'We cannot reach the database server. Try localhost or 127.0.0.1 and check the port.',
            'driver_missing' => 'The PHP extension for this database type is not enabled (pdo_mysql or pdo_pgsql).',
            'version_too_old' => 'The database version is too old. You need at least MySQL 5.7.8, MariaDB 10.3 or PostgreSQL 13.',
            'unknown' => 'Connecting to the database failed. Check the details and try again.',
        ],
    ],

    'academy' => [
        'title' => 'Platform and admin account',
        'lead' => 'This is your own teacher account — you will use it to sign in to the admin panel and manage everything.',
        'platform' => 'Platform',
        'account' => 'Admin account',
        'submit' => 'Install the platform',
        'wait' => 'Setting up the platform… this can take a minute. Do not close the page.',
        'password_hint' => 'At least 8 characters, with an uppercase letter, a lowercase letter and a number.',
        'fields' => [
            'platform_name' => 'Platform name',
            'teacher_name' => 'Teacher name',
            'subject' => 'Subject',
            'admin_email' => 'Email',
            'admin_phone' => 'Mobile number',
            'admin_password' => 'Password',
            'admin_password_confirmation' => 'Confirm password',
        ],
        'placeholders' => [
            'platform_name' => 'Success Academy',
            'teacher_name' => 'Mr. Mohamed Ahmed',
            'subject' => 'Physics',
        ],
    ],

    'steps' => [
        'lock' => 'Locking the installer',
        'database' => 'Connecting to the database',
        'migrations' => 'Creating the tables',
        'permissions' => 'Roles and permissions',
        'owner' => 'Platform and admin account',
        'storage' => 'Linking the files folder',
        'environment' => 'Writing the settings file (.env)',
        'tenancy' => 'Locking the run mode',
        'keys' => 'AI connection keys',
        'finish' => 'Finishing the installation',
    ],

    'errors' => [
        'already_installed' => 'The platform is already installed.',
        'step_failed' => 'The installation stopped at the ":step" step. Fix the cause and try again — finished steps will not be repeated.',
    ],

    'finished' => [
        'title' => 'Your platform is ready 🎉',
        'lead' => 'Sign in to the admin panel with :email and the password you chose.',
        'open_admin' => 'Open the admin panel',
        'open_site' => 'Open the site',
        'admin' => 'Admin panel',
        'cron' => 'Last step: scheduled tasks (cron)',
        'cron_help' => 'In cPanel → Cron Jobs → choose "Once Per Minute" and paste this line. It sends notifications, processes images and runs backups — without it those will not happen.',
        'cron_php_hint' => 'If your host has several PHP versions, replace php with the PHP 8.4 path (on cPanel usually /opt/cpanel/ea-php84/root/usr/bin/php, on Hostinger usually /opt/alt/php84/usr/bin/php).',
        'media' => 'Images and files',
        'media_modes' => [
            'symlink' => 'The public/storage link was created — the web server serves files directly.',
            'route' => 'Your host disables symlink(), so the platform serves files itself from /uploads. Nothing for you to do.',
        ],
        'next' => 'Next',
        'next_mail' => 'Set up mail (SMTP) in the settings so password reset emails are delivered — until then mail is only written to the log.',
        'next_ssl' => 'Make sure the certificate (SSL / HTTPS) is active on the domain — cPanel → SSL/TLS Status.',
        'next_backup' => 'Back up the database and the storage folder regularly.',
        'version' => 'Version :version',
    ],

    'cli' => [
        'already_installed' => 'The platform is already installed — nothing to do.',
        'done' => 'The platform is installed. Sign in with :email.',
    ],
];
