<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | وضع التجربة (Demo)
    |--------------------------------------------------------------------------
    | نسخة عرض: بيانات أكاديمية جاهزة، وحسابات بتدخلها بضغطة من صفحة الدخول.
    |
    |   DEMO_MODE=true  ثم  php artisan lms:demo
    |
    | الدخول بضغطة بيشتغل بس لو **الاتنين** صح: `DEMO_MODE=true` **و** المنصة
    | اتثبّتت بـ `lms:demo` (علامة `demo` في installed.json). أكاديمية حقيقية
    | اتفعّل عليها المتغيّر بالغلط مابتتفتحش لحد — ولا حساب غير اللي تحت بيتدخل.
    */

    'enabled' => (bool) env('DEMO_MODE', false),

    // كل الحسابات بنفس كلمة السر — للي عايز يدخل بالفورم العادي
    'password' => (string) env('DEMO_PASSWORD', 'demo-password'),

    // تصفير البيانات كل يوم (lms:demo --fresh) — لنسخة عرض مفتوحة للناس
    'auto_reset' => (bool) env('DEMO_AUTO_RESET', false),
    'reset_at' => (string) env('DEMO_RESET_AT', '04:00'),

    'academy' => [
        'platform_name' => 'منصة الكيمياء',
        'teacher_name' => 'أ. أحمد المصري',
        'subject' => 'كيمياء',
    ],

    /*
    | الحسابات اللي بتظهر في صفحات الدخول. `role` لحسابات اللوحة (من
    | config/authorization.php)، والطالب على حارس `student` برقم الموبايل.
    */
    'accounts' => [
        'teacher' => ['guard' => 'web', 'role' => 'admin', 'name' => 'أ. أحمد المصري', 'email' => 'teacher@demo.test', 'icon' => 'phosphor-chalkboard-teacher-bold'],
        'assistant' => ['guard' => 'web', 'role' => 'assistant', 'name' => 'مستر كريم (مساعد)', 'email' => 'assistant@demo.test', 'icon' => 'phosphor-user-gear-bold'],
        'viewer' => ['guard' => 'web', 'role' => 'viewer', 'name' => 'أ. منى (مشاهدة بس)', 'email' => 'viewer@demo.test', 'icon' => 'phosphor-eye-bold'],
        'student' => ['guard' => 'student', 'name' => 'محمد أحمد علي', 'phone' => '01000000000', 'icon' => 'phosphor-student-bold'],
    ],
];
