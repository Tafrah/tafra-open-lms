<?php

declare(strict_types=1);

return [
    'enums' => [
        'question_type' => [
            'multiple_choice' => 'Multiple choice',
            'essay' => 'Essay',
        ],
        'grading_status' => [
            'auto' => 'Automatic',
            'pending' => 'Awaiting grading',
            'graded' => 'Graded',
        ],
    ],
    'errors' => [
        'exam_closed' => 'This exam is not available right now.',
        'no_chances_left' => 'You have used all the allowed attempts.',
        'exam_empty' => 'This exam has no questions yet.',
        'attempt_not_yours' => 'This attempt is not yours.',
        'attempt_finished' => 'This attempt has already been submitted.',
        'time_up' => 'Time is up.',
        'question_not_in_exam' => 'This question is not part of the exam.',
        'choice_invalid' => 'That choice does not exist.',
        'mark_out_of_range' => 'The mark must be between 0 and :max.',
        'images_not_allowed' => 'This question does not accept images.',
        'image_invalid' => 'The image must be JPG, PNG or WEBP and smaller than :max MB.',
        'too_many_images' => 'At most :max images per question.',
        'image_not_found' => 'That image does not exist.',
        'import_headers' => 'The file is missing these columns: :columns. Download the template and fill it in.',
        'import_empty' => 'The file has no questions.',
        'import_too_many' => 'At most :max questions per file.',
        'import_unreadable' => 'We could not read the file. Save it as CSV (UTF-8) from Excel and try again.',
    ],
    'exam' => [
        'singular' => 'Exam',
        'plural' => 'Exams',
        'sections' => [
            'basic' => 'Details',
            'rules' => 'Rules',
            'display' => 'What students see',
        ],
        'fields' => [
            'name' => 'Exam name',
            'level' => 'Grade',
            'description' => 'Instructions for students',
            'duration_minutes' => 'Duration (minutes)',
            'allowed_chances' => 'Attempts allowed',
            'min_percentage' => 'Pass mark',
            'start_at' => 'Available from',
            'end_at' => 'Available until',
            'show_correct_answers' => 'Show correct answers after submit',
            'show_answers' => 'Correct answers for students',
            'show_answers_at' => 'Show from',
            'show_explanation' => 'Show explanations',
            'random_questions' => 'Shuffle questions',
            'random_choices' => 'Shuffle choices',
            'questions_count' => 'Questions',
            'published' => 'Published',
        ],
        'help' => [
            'duration' => 'Leave empty for no time limit.',
            'chances' => 'Leave empty for unlimited attempts.',
            'show_answers_at' => 'Before this date students only see their score — useful while other students are still taking the exam.',
        ],
        'show_answers' => [
            'never' => 'Never',
            'after_finish' => 'Right after submitting',
            'after_date' => 'After a set date',
        ],
        'actions' => [
            'publish' => 'Publish',
            'unpublish' => 'Stop',
        ],
        'notifications' => [
            'published' => 'The exam is published.',
        ],
        'empty' => [
            'heading' => 'No exams yet',
            'description' => 'Create an exam, add questions, then add it as an item inside any course section.',
        ],
    ],
    'tours' => [
        'exam' => [
            'purpose' => [
                'title' => 'Exams',
                'body' => 'Multiple-choice and essay exams. Choices are graded automatically; essays wait for you in “Student attempts”.',
            ],
            'table' => [
                'title' => 'Your exams',
                'body' => 'Only published exams can be taken by students.',
            ],
            'create' => [
                'title' => 'New exam',
                'body' => 'Set the options (duration, attempts, pass mark), then add questions from the edit page.',
            ],
        ],
        'attempt' => [
            'purpose' => [
                'title' => 'Student attempts',
                'body' => 'Every exam a student took: score, percentage and status. Attempts with essays wait for your grading.',
            ],
            'table' => [
                'title' => 'Attempts',
                'body' => 'Use the “Awaiting grading” filter to see what needs you. “Export results” downloads an Excel/CSV file with the current filters, and “Reset student attempts” lets one student retake the exam.',
            ],
        ],
    ],
    'question' => [
        'singular' => 'Question',
        'plural' => 'Questions',
        'fields' => [
            'type' => 'Type',
            'mark' => 'Mark',
            'text' => 'Question text',
            'choices' => 'Choices',
            'choice_text' => 'Choice',
            'is_correct' => 'Correct answer',
            'ideal_answer' => 'Model answer',
            'explanation' => 'Explanation',
            'image' => 'Question image',
            'choice_image' => 'Image (optional)',
            'explanation_image' => 'Explanation image',
            'allow_images' => 'Student can upload photos of the answer',
        ],
        'help' => [
            'ideal_answer' => 'Shown only to you while grading — not to the student.',
            'explanation' => 'Shown to the student after submitting (if enabled).',
            'allow_images' => 'Up to :max photos — the student photographs their written solution.',
        ],
        'validation' => [
            'one_correct' => 'Mark exactly one choice as correct.',
        ],
        'empty' => [
            'heading' => 'This exam has no questions yet',
            'description' => 'Add multiple-choice or essay questions.',
        ],
    ],
    'attempt' => [
        'singular' => 'Attempt',
        'plural' => 'Student attempts',
        'badge' => 'Attempts awaiting essay grading',
        'fields' => [
            'student' => 'Student',
            'exam' => 'Exam',
            'score' => 'Score',
            'percentage' => 'Percentage',
            'grading_status' => 'Grading',
            'chance' => 'Attempt #',
            'start_at' => 'Started',
            'end_at' => 'Submitted',
        ],
        'actions' => [
            'grade' => 'Grade',
            'view' => 'View',
            'reset' => 'Reset student attempts',
            'export' => 'Export results',
        ],
        'confirm' => [
            'reset' => 'All of this student\'s attempts on this exam will be deleted with their marks and answers, and the student can take it again with the full number of attempts. This cannot be undone.',
        ],
        'notifications' => [
            'reset' => '{0} The student has no attempts on this exam.|{1} One attempt was deleted — the student can retake the exam.|[2,*] :count attempts were deleted — the student can retake the exam.',
        ],
        'export' => [
            'phone' => 'Student phone',
            'max' => 'Full mark',
            'passed' => 'Passed?',
            'yes' => 'Yes',
            'no' => 'No',
            'finished_at' => 'Submitted at',
            'completed' => '{1} One result exported.|[2,*] :count results exported.',
        ],
        'empty' => [
            'heading' => 'No one has taken an exam yet',
            'description' => 'When a student submits an exam you will find the attempt here; essays wait for your grading.',
        ],
    ],
    'answer' => [
        'plural' => 'Answers',
        'blank' => '(no answer)',
        'fields' => [
            'question' => 'Question',
            'answer' => 'Student answer',
            'is_correct' => 'Correct?',
            'mark' => 'Mark',
            'grading_status' => 'Grading',
            'feedback' => 'Note to the student',
            'images' => 'Answer photos',
        ],
        'help' => [
            'max' => 'From 0 to :max',
            'ideal' => 'Model answer: :answer',
        ],
        'actions' => [
            'grade' => 'Grade',
        ],
        'notifications' => [
            'graded' => 'The mark was saved.',
        ],
        'empty' => [
            'heading' => 'No answers',
            'description' => 'The student submitted without answering.',
        ],
    ],
    'import' => [
        'actions' => [
            'import' => 'Import questions from a file',
            'template' => 'Download template',
        ],
        'fields' => [
            'file' => 'CSV file',
        ],
        'help' => [
            'file' => 'Multiple choice only. Columns: question, choice_1 … choice_6, correct (number of the right choice 1-6), mark, explanation. From Excel: “Save as” → CSV UTF-8.',
        ],
        'row_error' => 'Row :row: :errors',
        'more_errors' => '… and :count more rows with errors.',
        'errors' => [
            'question_required' => 'question text is empty',
            'question_too_long' => 'question text is longer than :max characters',
            'two_choices' => 'at least two choices are needed',
            'correct_invalid' => 'the correct column must be the number of a filled choice (1 to :max)',
            'mark_invalid' => 'the mark must be a number from 0.25 to 1000',
        ],
        'notifications' => [
            'imported' => '{1} One question was added.|[2,*] :count questions were added.',
            'failed' => 'No questions were added — fix these rows and upload the file again',
        ],
        'samples' => [
            [
                'question' => 'عاصمة مصر هي؟',
                'choices' => ['القاهرة', 'الإسكندرية', 'أسوان', 'الأقصر'],
                'correct' => '1',
                'mark' => '1',
                'explanation' => 'القاهرة هي عاصمة مصر من أيام الدولة الفاطمية.',
            ],
            [
                'question' => 'ناتج ٥ × ٦ = ؟',
                'choices' => ['٢٥', '٣٠', '٣٥'],
                'correct' => '2',
                'mark' => '2',
                'explanation' => '',
            ],
        ],
    ],
    'mcp' => [
        'actions' => [
            'publish' => 'Publishes the exam so students can take it.',
        ],
        'exempt' => [
            'attempts' => 'Attempts and grading hold student marks — panel only.',
        ],
    ],
    'console' => [
        'finalized' => 'Submitted :count expired attempts.',
    ],
];
