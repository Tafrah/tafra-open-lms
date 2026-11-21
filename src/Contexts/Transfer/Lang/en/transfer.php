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
];
