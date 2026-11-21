<?php

declare(strict_types=1);

return [
    'menu' => [
        'preferences' => 'تفضيلات الإشعارات',
    ],

    'preferences' => [
        'title' => 'تفضيلات الإشعارات',
        'description' => 'اختار توصلك إزاي كل نوع إشعار. القنوات الإجبارية مقفولة لأسباب أمنية.',
        'saved' => 'اتحفظت التفضيلات',
        'required' => 'إجباري',
        'required_hint' => 'الإشعار ده أمني — مينفعش توقفه.',
    ],

    'channels' => [
        'database' => 'داخل اللوحة',
        'mail' => 'البريد',
        'broadcast' => 'فوري',
    ],

    'groups' => [
        'identity' => 'الحساب',
        'security' => 'الأمان',
        'system' => 'النظام',
        'content' => 'المحتوى',
        'reports' => 'التقارير',
    ],

    'catalog' => [
        'user_invited' => [
            'label' => 'دعوة مستخدم جديد',
            'description' => 'لما حد يتدعى للمؤسسة.',
        ],
        'password_changed' => [
            'label' => 'تغيير كلمة المرور',
            'description' => 'تنبيه أمني عند تغيير كلمة مرور حسابك.',
        ],
        'new_device_login' => [
            'label' => 'دخول من جهاز جديد',
            'description' => 'تنبيه أمني عند أول دخول من جهاز ما دخلش قبل كده.',
        ],
        'export_ready' => [
            'label' => 'ملف التصدير جاهز',
            'description' => 'لما التصدير اللي طلبته يخلص.',
        ],
        'announcement_published' => [
            'label' => 'إعلان جديد',
            'description' => 'لما الإدارة تنشر إعلان للمؤسسة.',
        ],
        'weekly_digest' => [
            'label' => 'الملخص الأسبوعي',
            'description' => 'ملخص نشاط المؤسسة كل أسبوع.',
        ],
    ],

    'export' => [
        'subject' => 'ملف التصدير جاهز',
        'body' => 'تصدير :resource خلص — :rows صف.',
        'expiry' => 'الرابط صالح لفترة محدودة، نزّل الملف بدري.',
    ],

    'digest' => [
        'subject' => 'الملخص الأسبوعي — :organization',
        'intro' => 'ده اللي حصل في مؤسستك الأسبوع اللي فات:',
        'stats' => [
            'total' => 'إجمالي المستخدمين: :count',
            'joined' => 'مستخدمون جدد: :count',
            'invited' => 'دعوات لسه ما اتقبلتش: :count',
            'published' => 'إعلانات اتنشرت: :count',
            'drafts' => 'إعلانات مسودة: :count',
        ],
    ],

    'tours' => [
        'preferences' => [
            'purpose' => [
                'title' => 'اختار اللي يوصلك',
                'body' => 'حدد الإشعارات اللي عايزها وعلى أي قناة.',
            ],
            'matrix' => [
                'title' => 'إشعارات إجبارية',
                'body' => 'إشعارات الأمان مابتتقفلش — بتوصلك دايماً.',
            ],
        ],
    ],
];
