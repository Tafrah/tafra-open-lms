<?php

declare(strict_types=1);

return [
    'defaults' => [
        'subject' => 'المادة',
        'category' => 'المرحلة الثانوية',
        'bootcamp_category' => 'الكورسات',
        'exam_category' => 'الامتحانات',
    ],
    'errors' => [
        'no_academy' => 'مفيش أكاديمية لسه — ثبّت المنصة الأول.',
        'zip_missing' => 'إضافة zip في PHP مش متفعّلة على السيرفر.',
        'write_failed' => 'مقدرناش نكتب ملف التصدير — اتأكد إن مجلد storage قابل للكتابة.',
        'file_missing' => 'الملف مش موجود.',
        'not_a_zip' => 'الملف ده مش zip سليم.',
        'unsafe_zip' => 'الملف فيه مسارات مش آمنة — مرفوض.',
        'no_manifest' => 'الملف ناقصه manifest.json — مش ملف تصدير.',
        'wrong_format' => 'ده مش ملف تصدير من طفرة أو المنصة المجانية.',
        'newer_format' => 'الملف ده من نسخة أحدث (صيغة :version) — حدّث المنصة الأول.',
        'checksum' => 'الملف :file اتغيّر أو باظ — نزّل التصدير تاني.',
    ],
    'warnings' => [
        'unsupported_table' => 'الجدول :table مش مدعوم في المنصة المجانية — اتساب.',
        'missing_parent' => ':table: صف اتساب لأن :column بيشاور على حاجة مش موجودة.',
        'duplicate' => ':table: صف اتساب لأنه موجود بالفعل (نفس الكود).',
        'lesson_type' => 'درس من نوع «:type» مش مدعوم في المنصة المجانية — اتساب (متاح في طفرة).',
        'student_without_user' => 'طالب من غير رقم موبايل — اتساب.',
        'media_failed' => 'الملف :file ماتنقلش.',
    ],
    'console' => [
        'exported' => 'اتصدّرت الأكاديمية في: :path',
        'table' => 'الجدول',
        'read' => 'اتقرا',
        'imported' => 'اتضاف',
        'merged' => 'اتدمج',
        'skipped' => 'اتساب',
        'dry_run_done' => 'دي كانت تجربة — مفيش حاجة اتحفظت. شغّل الأمر من غير --dry-run للاستيراد الفعلي.',
        'imported_done' => 'الاستيراد خلص. الملفات اللي اتنقلت: :media',
    ],
];
