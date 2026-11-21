<?php

declare(strict_types=1);

return [
    /*
    | ديسك ملفات النقل المؤقتة (الـ zip المرفوع/المصدَّر) — لازم يكون خاص.
    */
    'disk' => env('TRANSFER_DISK', 'local'),

    // أقصى حجم لملف الاستيراد من اللوحة (كيلوبايت). الاستضافة المشتركة غالبًا ٦٤–٢٥٦ ميجا.
    'max_upload_kb' => (int) env('TRANSFER_MAX_UPLOAD_KB', 262144),
];
