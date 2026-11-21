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
    ],
];
