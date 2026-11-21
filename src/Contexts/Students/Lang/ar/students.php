<?php

declare(strict_types=1);

return [
    'enums' => [
        'student_status' => [
            'active' => 'نشط',
            'blocked' => 'محظور',
        ],
        'school_type' => [
            'general' => 'عام',
            'azhar' => 'أزهر',
        ],
    ],
    'errors' => [
        'phone_taken' => 'رقم الموبايل ده متسجل قبل كده.',
    ],
    'student' => [
        'singular' => 'طالب',
        'plural' => 'الطلبة',
        'empty_related' => 'مفيش حاجة لسه.',
        'sections' => [
            'info' => 'بيانات الطالب',
        ],
        'fields' => [
            'name' => 'الاسم',
            'phone' => 'الموبايل',
            'parent_phone' => 'موبايل ولي الأمر',
            'email' => 'الإيميل',
            'level' => 'الصف',
            'governorate' => 'المحافظة',
            'school_type' => 'نوع التعليم',
            'password' => 'كلمة السر',
            'new_password' => 'كلمة السر الجديدة',
            'code' => 'الكود',
            'balance' => 'الرصيد',
            'bootcamp' => 'الكورس',
            'transaction_type' => 'نوع الحركة',
            'amount' => 'المبلغ',
            'notes' => 'ملاحظة',
        ],
        'relations' => [
            'enrollments' => 'الاشتراكات',
            'transactions' => 'حركات المحفظة',
            'attempts' => 'الامتحانات',
        ],
        'actions' => [
            'enroll' => 'اشتراك في كورس',
            'unenroll' => 'إلغاء اشتراك',
            'wallet' => 'تعديل الرصيد',
            'reset_password' => 'تغيير كلمة السر',
            'block' => 'إيقاف الحساب',
            'unblock' => 'تفعيل الحساب',
        ],
        'notifications' => [
            'enrolled' => 'الطالب اشترك في الكورس.',
            'unenrolled' => 'الاشتراك اتلغى.',
            'wallet' => 'الرصيد اتعدّل.',
            'password' => 'كلمة السر اتغيّرت.',
        ],
        'empty' => [
            'heading' => 'لسه مفيش طلبة',
            'description' => 'ابعت رابط منصتك للطلبة — أول ما حد يسجّل هيظهر هنا.',
        ],
        'validation' => [
            'phone' => 'رقم الموبايل لازم يكون مصري ١١ رقم (مثلًا 01012345678).',
        ],
    ],
    'tours' => [
        'student' => [
            'purpose' => [
                'title' => 'الطلبة',
                'body' => 'كل طالب سجّل على منصتك. من هنا تشترك له يدوي، تشحن رصيده، تغيّر كلمة السر، أو توقفه.',
            ],
            'table' => [
                'title' => 'الطلبة',
                'body' => 'دوّر بالاسم أو الموبايل أو الكود، وفلتر بالصف.',
            ],
            'create' => [
                'title' => 'طالب جديد',
                'body' => 'تقدر تسجّل طالب بنفسك (مثلًا طالب سنتر).',
            ],
        ],
    ],
    'mcp' => [
        'omitted' => [
            'money' => 'أي حركة فلوس قرار للمدرس من اللوحة — مش لنموذج ذكاء اصطناعي.',
            'access' => 'فتح وقفل الكورسات بيغيّر مين يدخل على إيه — من اللوحة بس.',
            'account' => 'كلمة السر وإيقاف الحساب إجراءات أمان — من اللوحة بس.',
        ],
    ],
    'widget' => [
        'students' => 'الطلبة',
        'new_students' => ':count جديد آخر ٧ أيام',
        'revenue' => 'دخل الشهر',
        'revenue_hint' => 'الفواتير المدفوعة من أول الشهر',
        'pending_receipts' => 'إيصالات مستنياك',
        'pending_receipts_hint' => 'راجعها عشان الكورس يتفتح للطالب',
        'pending_essays' => 'مقالي مستني تصحيح',
        'pending_essays_hint' => 'محاولات فيها أسئلة مقالي',
    ],
];
