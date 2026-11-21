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
            'show_explanation' => 'Show explanations',
            'random_questions' => 'Shuffle questions',
            'random_choices' => 'Shuffle choices',
            'questions_count' => 'Questions',
            'published' => 'Published',
        ],
        'help' => [
            'duration' => 'Leave empty for no time limit.',
            'chances' => 'Leave empty for unlimited attempts.',
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
                'body' => 'Use the “Awaiting grading” filter to see what needs you.',
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
        ],
        'help' => [
            'ideal_answer' => 'Shown only to you while grading — not to the student.',
            'explanation' => 'Shown to the student after submitting (if enabled).',
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
