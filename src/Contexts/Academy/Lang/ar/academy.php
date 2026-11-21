<?php

declare(strict_types=1);

return [
    'enums' => [
        'publish_status' => [
            'draft' => 'مسودة',
            'published' => 'منشور',
        ],
        'lesson_type' => [
            'YoutubeVideo' => 'فيديو يوتيوب',
            'Pdf' => 'ملف PDF',
            'Text' => 'نص',
        ],
        'section_item_type' => [
            'lesson' => 'درس',
            'exam' => 'امتحان',
        ],
    ],
    'errors' => [
        'item_unavailable' => 'العنصر ده مش متاح دلوقتي.',
        'login_required' => 'سجّل دخول الأول.',
        'not_enrolled' => 'اشترك في الكورس عشان تفتح الدرس ده.',
        'prerequisite_exam' => 'لازم تنجح في الامتحان اللي قبله الأول.',
        'views_exhausted' => 'خلصت عدد المشاهدات المسموح بيه للدرس ده.',
    ],
];
