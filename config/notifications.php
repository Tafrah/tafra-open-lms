<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| كتالوج الإشعارات
|--------------------------------------------------------------------------
| كل إشعار في التطبيق لازم يكون هنا — فيه اختبار بيفشل لو واحد ناقص.
|
| channels : القنوات المتاحة للمستخدم يختار منها
| default  : اللي شغّال قبل ما يغيّر حاجة
| required : مايقدرش يوقفه (أمني أو قانوني)
*/

return [

    'catalog' => [

        'user_invited' => [
            'group' => 'identity',
            'channels' => ['database', 'mail'],
            'default' => ['database', 'mail'],
            'required' => ['database'],
        ],

        'password_changed' => [
            'group' => 'security',
            'channels' => ['database', 'mail'],
            'default' => ['database', 'mail'],
            'required' => ['mail'],
        ],

        'new_device_login' => [
            'group' => 'security',
            'channels' => ['database', 'mail'],
            'default' => ['database', 'mail'],
            'required' => ['mail'],
        ],

        // إشعار اللوحة بيبعته Filament بنفسه (هو اللي فيه رابط التنزيل)
        // فمينفعش يتقفل. اللي المستخدم بيتحكّم فيه هو البريد الإضافي.
        'export_ready' => [
            'group' => 'system',
            'channels' => ['database', 'mail'],
            'default' => ['database'],
            'required' => ['database'],
        ],

        'announcement_published' => [
            'group' => 'content',
            'channels' => ['database', 'broadcast', 'mail'],
            'default' => ['database', 'broadcast'],
            'required' => [],
        ],

        'weekly_digest' => [
            'group' => 'reports',
            'channels' => ['mail'],
            'default' => [],
            'required' => [],
        ],

        // ═══ إشعارات الطالب على الموقع (الجرس) ═══
        // `audience => students`: مش بتظهر في تفضيلات مستخدمي اللوحة —
        // الطالب مالوش شاشة تفضيلات، والقناة دايمًا database.
        'student_invoice_reviewed' => [
            'group' => 'students',
            'audience' => 'students',
            'channels' => ['database'],
            'default' => ['database'],
            'required' => ['database'],
        ],

        'student_attempt_graded' => [
            'group' => 'students',
            'audience' => 'students',
            'channels' => ['database'],
            'default' => ['database'],
            'required' => ['database'],
        ],
    ],

    /** الطابور اللي كل الإشعارات بتروح عليه */
    'queue' => 'notifications',
];
