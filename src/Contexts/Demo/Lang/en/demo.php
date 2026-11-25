<?php

declare(strict_types=1);

return [
    'badge' => 'Demo',

    'banner' => [
        'body' => 'This is a demo with sample data — feel free to explore.',
        'resets' => 'This is a demo with sample data — it resets every day at :time',
        'admin_link' => 'Try the teacher panel',
    ],

    'login' => [
        'title' => 'One-click sign in',
        'password_hint' => 'Or sign in with the email; the password for every account is:',
        'student_title' => 'Try the site as a student',
        'as_student' => 'Sign in as the demo student',
        'student_credentials' => 'Or use the form:',
    ],

    'accounts' => [
        'teacher' => ['label' => 'Teacher (owner)', 'hint' => 'Full access: courses, money, settings'],
        'assistant' => ['label' => 'Assistant', 'hint' => 'Reviews receipts, grades, manages students'],
        'viewer' => ['label' => 'Viewer', 'hint' => 'Read-only'],
        'student' => ['label' => 'Student', 'hint' => 'Enrolled in two courses, with EGP 300 in the wallet.'],
    ],

    'cli' => [
        'disabled' => 'Demo mode is off — set DEMO_MODE=true first.',
        'real_install' => 'This platform is a real installation (not a demo) — lms:demo will not touch it.',
        'already' => 'The demo is already installed.',
        'already_hint' => 'The demo is already installed — use --fresh to start over.',
        'wiping' => 'Wiping the database',
        'seeding' => 'Demo data (students, enrollments, receipts, exams)',
        'done' => 'The demo is ready. Accounts:',
        'admin_url' => 'Teacher panel',
        'site_url' => 'Student sign in',
    ],
];
