<?php

declare(strict_types=1);

return [
    'enums' => [
        'question_type' => [
            'multiple_choice' => 'اختيار من متعدد',
            'essay' => 'مقالي',
        ],
        'grading_status' => [
            'auto' => 'تلقائي',
            'pending' => 'مستني تصحيح',
            'graded' => 'اتصحح',
        ],
    ],
    'errors' => [
        'exam_closed' => 'الامتحان ده مش متاح دلوقتي.',
        'no_chances_left' => 'خلصت عدد المحاولات المسموح بيه.',
        'exam_empty' => 'الامتحان لسه مافيهوش أسئلة.',
        'attempt_not_yours' => 'المحاولة دي مش بتاعتك.',
        'attempt_finished' => 'المحاولة دي اتسلّمت خلاص.',
        'time_up' => 'الوقت خلص.',
        'question_not_in_exam' => 'السؤال ده مش في الامتحان.',
        'choice_invalid' => 'الاختيار ده مش موجود.',
        'mark_out_of_range' => 'الدرجة لازم تكون من ٠ لـ :max.',
    ],
];
