<?php

declare(strict_types=1);

return [
    'defaults' => [
        'subject' => 'Subject',
        'category' => 'Secondary stage',
        'bootcamp_category' => 'Courses',
        'exam_category' => 'Exams',
    ],
    'errors' => [
        'no_academy' => 'There is no academy yet — install the platform first.',
        'zip_missing' => 'The PHP zip extension is not enabled on the server.',
        'write_failed' => 'Could not write the export file — make sure storage/ is writable.',
        'file_missing' => 'The file does not exist.',
        'not_a_zip' => 'This is not a valid zip file.',
        'unsafe_zip' => 'The file contains unsafe paths — rejected.',
        'no_manifest' => 'manifest.json is missing — this is not an export file.',
        'wrong_format' => 'This is not an export from Tafra or the free platform.',
        'newer_format' => 'This file comes from a newer version (format :version) — update the platform first.',
        'checksum' => 'The file :file was changed or is corrupted — download the export again.',
        'already_imported' => 'This file was already imported into this platform — it will not be imported twice.',
    ],
    'warnings' => [
        'unsupported_table' => 'Table :table is not supported in the free platform — skipped.',
        'missing_parent' => ':table: a row was skipped because :column points to something missing.',
        'duplicate' => ':table: a row was skipped because it already exists (same code).',
        'lesson_type' => 'A “:type” lesson is not supported in the free platform — skipped (available in Tafra).',
        'student_without_user' => 'A student without a mobile number — skipped.',
        'media_failed' => 'The file :file was not transferred.',
    ],
    'console' => [
        'exported' => 'Academy exported to: :path',
        'table' => 'Table',
        'read' => 'Read',
        'imported' => 'Imported',
        'merged' => 'Merged',
        'skipped' => 'Skipped',
        'dry_run_done' => 'This was a dry run — nothing was saved. Run without --dry-run to import.',
        'imported_done' => 'Import finished. Files transferred: :media',
    ],
    'page' => [
        'title' => 'Data transfer',
        'description' => 'Download a full copy of your platform, or import one — from the free platform or from Tafra.',
        'export' => 'Export academy',
        'import' => 'Import file',
        'file' => 'Export file (zip)',
        'try' => 'Try first',
        'confirm' => 'Import for real',
        'confirm_help' => 'The data will be added to your platform. Existing students with the same mobile are merged, not duplicated.',
        'import_help' => 'Upload a zip from “Export academy” here or from Tafra. We do a dry run first and show what will move — nothing is saved until you confirm.',
        'done' => 'Import finished.',
        'export_card' => 'Backup / move to Tafra',
        'export_body' => 'One file with everything: courses, lessons, exams, students (with passwords), enrollments, wallets, codes and images. Upload it to Tafra and students log in with the same mobile and password.',
        'import_card' => 'Import',
        'import_body' => 'Coming from Tafra or another copy of the platform: upload the file, check the report, then confirm.',
        'dry_run_report' => 'Dry-run report',
        'import_report' => 'Import report',
        'dry_run_note' => 'This is a dry run — nothing has been saved yet. If the report looks right, press “Import for real”.',
    ],
    'tours' => [
        'tafra' => [
            'purpose' => [
                'title' => 'Tafra features',
                'body' => 'Things that are in Tafra but not in the open edition — not ads, just here if you need one.',
            ],
            'contact' => [
                'title' => 'Contact us',
                'body' => 'Each feature has a contact button if you want to know more.',
            ],
        ],
        'page' => [
            'purpose' => [
                'title' => 'Data transfer',
                'body' => 'Take a full copy of your platform or import one — the same format Tafra understands.',
            ],
            'actions' => [
                'title' => 'Export and import',
                'body' => 'Export downloads a zip. Import does a dry run first and shows you the report before saving.',
            ],
        ],
    ],
    'tafra' => [
        'title' => 'Tafra features',
        'subheading' => 'The open edition has everything a teacher needs to start. When you need more, these are in Tafra.',
        'contact' => 'Contact us',
        'whatsapp_message' => 'Hi, I am a teacher using the open edition of Tafra and want to know more about: :feature',
        'features' => [
            'payments' => ['title' => 'Online payments that activate by themselves', 'body' => 'Fawry, bank cards and wallets through payment gateways — the student pays and the lecture opens instantly, no receipts to review.'],
            'whatsapp' => ['title' => 'WhatsApp messages to parents', 'body' => 'Exam grades, absences and payment reminders reach parents automatically on WhatsApp.'],
            'app' => ['title' => 'A mobile app with your name', 'body' => 'Android and iOS apps with your logo, push notifications and stronger video protection.'],
            'paper_exams' => ['title' => 'Paper exams with automatic marking', 'body' => 'Print the answer sheet, scan it, and every student\'s marks are recorded.'],
            'attendance' => ['title' => 'Centre attendance and schedules', 'body' => 'Class timetable, attendance by code, and absent lists messaged to parents.'],
            'ai' => ['title' => 'AI essay grading', 'body' => 'A suggested mark and comment for each essay answer — you review and approve.'],
            'store' => ['title' => 'Books and notes store', 'body' => 'Sell books and notes online with shipping and pickup points.'],
            'support' => ['title' => 'Support and managed hosting', 'body' => 'We host, update and back up the platform; you focus on teaching.'],
        ],
        'move' => [
            'title' => 'Want to move to Tafra?',
            'body' => 'Export all your data in one file — students, courses, exams and wallets — Tafra imports it and students log in with the same passwords.',
            'action' => 'Export data',
        ],
        'nudge' => [
            'students' => 'Your platform passed :count students 🎉 — in Tafra online payments activate lectures by themselves and parent messages go out automatically.',
            'receipts' => 'You reviewed :count receipts this week — taking your time? In Tafra payments activate automatically.',
            'action' => 'Learn more',
        ],
    ],
];
