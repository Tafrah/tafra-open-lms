<?php

declare(strict_types=1);

return [
    /*
    | ديسك ملفات النقل المؤقتة (الـ zip المرفوع/المصدَّر) — لازم يكون خاص.
    */
    'disk' => env('TRANSFER_DISK', 'local'),

    // أقصى حجم لملف الاستيراد من اللوحة (كيلوبايت). الاستضافة المشتركة غالبًا ٦٤–٢٥٦ ميجا.
    'max_upload_kb' => (int) env('TRANSFER_MAX_UPLOAD_KB', 262144),

    /*
    | «مميزات طفرة» في آخر السايدبار + رسالة لما المنصة تكبر (plan بند ٩).
    | TAFRA_WHATSAPP (رقم دولي من غير +) بيخلّي زرار التواصل يفتح واتساب برسالة
    | جاهزة، وإلا بيفتح موقع طفرة. LMS_SHOW_TAFRA=false بيخفي الاتنين.
    */
    'tafra' => [
        'show_features' => (bool) env('LMS_SHOW_TAFRA', true),
        'url' => env('TAFRA_URL', 'https://tafra-tech.com'),
        'whatsapp' => env('TAFRA_WHATSAPP', ''),
        // الرسالة بتظهر على الرئيسية لما المنصة تعدّي أي رقم من دول
        'nudge' => ['students' => 500, 'receipts_per_week' => 100],
    ],
];
