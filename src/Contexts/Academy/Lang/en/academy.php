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
    'level' => [
        'singular' => 'Grade',
        'plural' => 'Grades',
        'fields' => [
            'name' => 'Name',
            'code' => 'Code',
            'color' => 'Color',
            'order' => 'Order',
            'students_count' => 'Students',
            'bootcamps_count' => 'Courses',
        ],
        'help' => [
            'code' => 'A short code shown on the course card (e.g. 3SEC).',
        ],
        'empty' => [
            'heading' => 'No grades yet',
            'description' => 'Add the grades you teach — students pick one when they sign up.',
        ],
    ],
    'tours' => [
        'level' => [
            'purpose' => [
                'title' => 'Grades',
                'body' => 'The grades you teach (1st/2nd/3rd secondary). Students pick their grade on sign-up and courses are grouped by it.',
            ],
            'table' => [
                'title' => 'The list',
                'body' => 'Each grade with its code, color and student count.',
            ],
            'create' => [
                'title' => 'Add a grade',
                'body' => 'Add the grades you teach once, at the start.',
            ],
        ],
        'lesson' => [
            'purpose' => [
                'title' => 'Lesson library',
                'body' => 'All your lessons in one place: YouTube video, PDF or text. One lesson can be used in several courses.',
            ],
            'table' => [
                'title' => 'Lessons',
                'body' => 'Filter by type and search by name.',
            ],
            'create' => [
                'title' => 'New lesson',
                'body' => 'Pick a type, then paste the YouTube link or upload the file.',
            ],
        ],
        'bootcamp' => [
            'purpose' => [
                'title' => 'Courses',
                'body' => 'A course (or lecture) is what students buy. It has sections, and each section has lessons and exams.',
            ],
            'table' => [
                'title' => 'Your courses',
                'body' => 'Published courses show on the site; drafts do not.',
            ],
            'create' => [
                'title' => 'New course',
                'body' => 'Set the name, price and image, then add sections from the edit page.',
            ],
        ],
        'section' => [
            'purpose' => [
                'title' => 'Section content',
                'body' => 'Order the section lessons and exams by dragging. Each item points to a library lesson or an exam.',
            ],
            'table' => [
                'title' => 'Sections',
                'body' => 'All your course sections.',
            ],
        ],
    ],
    'lesson' => [
        'singular' => 'Lesson',
        'plural' => 'Lessons',
        'sections' => [
            'content' => 'Lesson content',
        ],
        'fields' => [
            'name' => 'Lesson name',
            'type' => 'Type',
            'duration_minutes' => 'Duration (minutes)',
            'youtube_url' => 'YouTube video link',
            'pdf' => 'PDF file',
            'text' => 'Lesson text',
            'description' => 'Short description',
            'used_in' => 'Used in',
        ],
        'help' => [
            'youtube_url' => 'Set the video to Unlisted on YouTube so it does not show in search.',
        ],
        'validation' => [
            'youtube' => 'This is not a valid YouTube video link.',
        ],
        'empty' => [
            'heading' => 'The lesson library is empty',
            'description' => 'Add your first lesson: a YouTube video, a PDF or a text lesson.',
        ],
    ],
    'bootcamp' => [
        'singular' => 'Course',
        'plural' => 'Courses',
        'was' => 'was :price',
        'sections' => [
            'basic' => 'Basics',
            'pricing' => 'Price & discount',
            'display' => 'Display',
        ],
        'fields' => [
            'name' => 'Course name',
            'slug' => 'Link',
            'level' => 'Grade',
            'promo_video' => 'Promo video (YouTube)',
            'short_description' => 'Short description',
            'description' => 'Description',
            'thumbnail' => 'Course image',
            'price' => 'Price',
            'discount_price' => 'Discount price',
            'discount_start_date' => 'Discount starts',
            'discount_end_date' => 'Discount ends',
            'can_watch_in_days' => 'Access period',
            'is_featured' => 'Featured',
            'featured_order' => 'Featured order',
            'sections_count' => 'Sections',
        ],
        'help' => [
            'slug' => 'Shown in the course link on the site (English letters, numbers and dashes).',
            'thumbnail' => '16:9 — e.g. 1280×720.',
            'price' => 'Zero = free course.',
            'can_watch_in_days' => 'Leave empty = lifetime access. If you enter 30, access ends after 30 days.',
            'is_featured' => 'Shown in “Suggested courses” on the home page.',
        ],
        'actions' => [
            'publish' => 'Publish',
            'unpublish' => 'Hide from site',
        ],
        'notifications' => [
            'published' => 'The course is published and visible to students.',
            'unpublished' => 'The course is hidden from the site.',
        ],
        'empty' => [
            'heading' => 'No courses yet',
            'description' => 'Create your first course: name, price and image, then add sections and lessons.',
        ],
    ],
    'currency' => 'EGP',
    'free' => 'Free',
    'days' => 'days',
    'section' => [
        'singular' => 'Section',
        'plural' => 'Sections',
        'fields' => [
            'name' => 'Section name',
            'bootcamp' => 'Course',
            'publish_status' => 'Status',
            'prerequisite_exam' => 'Exam to pass first',
            'is_free_preview' => 'Free preview',
            'items_count' => 'Items',
        ],
        'help' => [
            'prerequisite_exam' => 'This section opens only after the student passes this exam.',
            'is_free_preview' => 'Anyone can watch it without enrolling — good for marketing.',
        ],
        'actions' => [
            'content' => 'Content',
        ],
        'empty' => [
            'heading' => 'This course has no sections yet',
            'description' => 'Split the course into sections (Week 1, Chapter 1…) then add lessons inside each.',
        ],
    ],
    'item' => [
        'singular' => 'Item',
        'plural' => 'Section lessons & exams',
        'fields' => [
            'type' => 'Type',
            'lesson' => 'Lesson',
            'exam' => 'Exam',
            'name' => 'Name shown to students',
            'publish_status' => 'Status',
            'allowed_views_number' => 'Allowed views',
            'prerequisite_exam' => 'Exam to pass first',
            'is_free_preview' => 'Free preview',
        ],
        'help' => [
            'name' => 'Leave empty to use the lesson or exam name.',
            'allowed_views_number' => 'Leave empty for unlimited views.',
        ],
        'empty' => [
            'heading' => 'This section is empty',
            'description' => 'Add a lesson from the library or an exam.',
        ],
    ],
    'mcp' => [
        'actions' => [
            'publish' => 'Publishes the course so students can see it on the site.',
        ],
        'exempt' => [
            'sections' => 'Course sections are managed inside the course page in the panel.',
        ],
    ],
];
