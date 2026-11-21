<?php

declare(strict_types=1);

return [
    'enums' => [
        'publish_status' => [
            'draft' => 'Draft',
            'published' => 'Published',
        ],
        'lesson_type' => [
            'YoutubeVideo' => 'YouTube video',
            'Pdf' => 'PDF file',
            'Text' => 'Text',
        ],
        'section_item_type' => [
            'lesson' => 'Lesson',
            'exam' => 'Exam',
        ],
    ],
    'errors' => [
        'item_unavailable' => 'This item is not available right now.',
        'login_required' => 'Please log in first.',
        'not_enrolled' => 'Enroll in the course to open this lesson.',
        'prerequisite_exam' => 'You need to pass the previous exam first.',
        'views_exhausted' => 'You have used all the allowed views for this lesson.',
    ],
];
