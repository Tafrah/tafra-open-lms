<?php

declare(strict_types=1);

return [

    'actions' => [
        'view_any' => 'عرض القائمة',
        'view' => 'عرض التفاصيل',
        'create' => 'إضافة',
        'update' => 'تعديل',
        'delete' => 'حذف',
        'delete_any' => 'حذف جماعي',
        'restore' => 'استرجاع',
        'force_delete' => 'حذف نهائي',
        'export' => 'تصدير',
        'impersonate' => 'انتحال الشخصية',
        'reset_password' => 'إعادة تعيين كلمة المرور',
        'force_logout' => 'إنهاء الجلسات',
        'manage_roles' => 'إدارة الأدوار',
        'download' => 'تنزيل',
        'change_disk' => 'تغيير مكان التخزين',
        'prune' => 'تنظيف السجلات',
        'publish' => 'نشر',
        'pin' => 'تثبيت',
        'suspend' => 'إيقاف مؤقت',
        'manage_storage' => 'إدارة التخزين',
        'manage_mail' => 'إدارة البريد',
        'manage_appearance' => 'إدارة المظهر',
        'manage_security' => 'إدارة الأمان',
        'manage_notifications' => 'إدارة الإشعارات',
        'manage_ai' => 'إدارة وصول الذكاء الاصطناعي',
        'grade' => 'تصحيح',
        'adjust_wallet' => 'تعديل رصيد المحفظة',
        'enroll' => 'اشتراك في كورس',
        'block' => 'حظر',
        'approve' => 'قبول',
        'reject' => 'رفض',
        'print' => 'طباعة',
        'manage_devices' => 'إدارة أجهزة الطالب',
        'reset' => 'إعادة المحاولات',
        'grant_views' => 'مشاهدات زيادة لطالب',
    ],

    'resources' => [
        'users' => 'المستخدمون',
        'roles' => 'الأدوار',
        'tenants' => 'المؤسسات',
        'announcements' => 'الإعلانات',
        'media' => 'الوسائط',
        'settings' => 'الإعدادات',
        'activity_logs' => 'سجل النشاط',
        'ai_connections' => 'تطبيقات الذكاء الاصطناعي المربوطة',
        'educational_levels' => 'الصفوف الدراسية',
        'online_lessons' => 'الدروس',
        'bootcamps' => 'الكورسات',
        'online_exams' => 'الامتحانات',
        'exam_attempts' => 'محاولات الامتحانات',
        'students' => 'الطلبة',
        'enrollments' => 'الاشتراكات',
        'payment_methods' => 'طرق الدفع',
        'payment_invoices' => 'الفواتير والإيصالات',
        'money_transactions' => 'حركات المحفظة',
        'recharge_cards' => 'أكواد الشحن',
        'coupons' => 'الكوبونات',
    ],

    'groups' => [
        'identity' => 'الهوية والوصول',
        'tenancy' => 'المؤسسات',
        'central' => 'اللوحة المركزية',
        'content' => 'المحتوى',
        'system' => 'النظام',
        'dashboard' => 'لوحة المعلومات',
        'ai' => 'وصول الذكاء الاصطناعي',
        'academy' => 'المحتوى التعليمي',
        'exams' => 'الامتحانات',
        'students' => 'الطلبة',
        'billing' => 'المالية',
    ],

    'pages' => [
        'access.panel.admin' => 'الدخول للوحة التحكم',
        'access.panel.central' => 'الدخول للوحة المركزية',
        'access.dashboard' => 'الدخول للوحة المعلومات',
        'access.horizon' => 'الدخول لـ Horizon',
        'access.pulse' => 'الدخول لـ Pulse',
        'access.health' => 'صفحة صحة النظام',
        'access.log_viewer' => 'عارض السجلات',
        'access.backups' => 'النسخ الاحتياطي',
        'access.system' => 'منطقة النظام',
        'access.tenancy' => 'منطقة المؤسسات',
        'access.organization_profile' => 'ملف المؤسسة',
        'access.onboarding_analytics' => 'مقاييس الأونبوردنج',
        'access.notification_preferences' => 'تفضيلات الإشعارات',
        'access.my_devices' => 'أجهزتي',
        'access.two_factor' => 'المصادقة الثنائية',
        'access.ai' => 'ربط تطبيقات الذكاء الاصطناعي (Claude وChatGPT)',
        'access.data_transfer' => 'نقل البيانات من وإلى طفرة',
        'access.site_settings' => 'إعدادات الموقع',
        'access.revenue_report' => 'تقرير الدخل',
    ],

    'widgets' => [
        'widget.stats_overview' => 'ودجت الإحصائيات',
        'widget.users_chart' => 'ودجت المستخدمين',
        'widget.recent_activity' => 'ودجت آخر النشاطات',
        'widget.onboarding_checklist' => 'ودجت قائمة البداية',
        'widget.lms_overview' => 'ملخص المنصة التعليمية',
    ],

    'roles' => [
        'super_admin' => 'مدير عام',
        'support' => 'دعم فني',
        'admin' => 'مدير',
        'editor' => 'محرّر',
        'viewer' => 'مشاهد',
        'assistant' => 'مساعد',
    ],

    'sync' => [
        'operation' => 'العملية',
        'count' => 'العدد',
        'to_create' => 'إضافة',
        'to_prune' => 'حذف محتمل',
        'done' => 'تمت المزامنة ومسح الكاش.',
    ],

    'denied' => [
        // عام
        'missing_permission' => 'مش معاك صلاحية «:permission».',
        'record_trashed' => 'السجل ده محذوف. استرجعه الأول.',
        'record_not_trashed' => 'السجل ده مش محذوف أصلاً.',
        'record_not_found' => 'السجل ده مش موجود.',
        'maintenance_mode' => 'النظام في وضع الصيانة دلوقتي.',
        'self_target' => 'مينفعش تعمل كده على حسابك.',
        'last_admin' => 'ده آخر مدير في المؤسسة — مينفعش تشيله.',
        'while_impersonating' => 'العملية دي مقفولة أثناء انتحال الشخصية.',

        // المستخدمون
        'user' => [
            'super_admin_only' => 'حساب المدير العام مايتلمسش إلا من مدير عام.',
            'cannot_impersonate_super_admin' => 'مينفعش تنتحل شخصية مدير عام.',
            'cannot_impersonate_staff' => 'مينفعش تنتحل شخصية حد من فريق التشغيل.',
            'inactive_target' => 'الحساب ده موقوف — فعّله الأول.',
        ],

        // الأدوار والصلاحيات
        'role' => [
            'protected' => 'الدور ده جاي من الكونفيج ومشترك بين كل المؤسسات — مايتعدّلش ولا يتحذف من هنا.',
            'still_assigned' => 'الدور ده لسه مسنّد لمستخدمين. شيله منهم الأول.',
            'other_tenant' => 'الدور ده مش موجود.',
        ],
        'permission' => [
            'config_owned' => 'الصلاحيات مصدرها ملف الكونفيج — عدّلها هناك وشغّل authorization:sync.',
        ],

        'settings' => [
            'global_group' => 'المجموعة دي إعدادات عامة — يديرها المدير العام بس.',
            'tenant_required' => 'إعدادات المؤسسة بتتغيّر من جوه المؤسسة بس.',
        ],

        // المؤسسات
        'tenancy' => [
            'last_active_tenant' => 'دي آخر مؤسسة نشطة — مينفعش تقفلها.',
            'already_inactive' => 'المؤسسة دي متوقّفة بالفعل.',
            'already_suspended' => 'المؤسسة دي موقوفة بالفعل.',
            'not_suspended' => 'المؤسسة دي مش موقوفة.',
            'not_provisioned' => 'المؤسسة دي لسه بتتجهّز.',
            'no_admin_to_impersonate' => 'مفيش مدير نشط في المؤسسة دي تدخل بصفته.',
        ],

        // وصول الذكاء الاصطناعي (MCP)
        'ai' => [
            'inactive_account' => 'الحساب ده مش نشط.',
            'not_a_member' => 'إنت مش عضو في المؤسسة دي.',
            'tenant_unavailable' => 'المؤسسة دي مش نشطة أو موقوفة.',
            'disabled' => 'وصول الذكاء الاصطناعي مقفول للمؤسسة دي.',
            'already_revoked' => 'التطبيق ده مفصول أصلاً.',
        ],

        // سجل النشاط
        'audit' => [
            'append_only' => 'سجل التدقيق مايتعدّلش ولا يتحذف — ده اللي بيديله قيمة.',
        ],

        // الإعلانات
        'announcement' => [
            'not_draft' => 'الإعلان ده منشور بالفعل.',
            'published_must_archive' => 'الإعلان المنشور بيتأرشف مش بيتحذف.',
            'missing_fallback_title' => 'لازم تكتب العنوان بلغة :locale قبل النشر.',
            'already_expired' => 'تاريخ انتهاء الإعلان عدّى — عدّله الأول.',
            'pin_requires_published' => 'مينفعش تثبّت إعلان مش منشور.',
            'pin_limit_reached' => 'وصلت للحد الأقصى (:limit) من الإعلانات المثبّتة.',
            'published_needs_publish_permission' => 'الإعلان منشور — تعديله محتاج صلاحية النشر.',
        ],
        // المنصة التعليمية
        'bootcamp' => [
            'already_published' => 'الكورس ده منشور بالفعل.',
        ],
        'exam' => [
            'already_published' => 'الامتحان ده منشور بالفعل.',
            'no_questions' => 'ضيف سؤال واحد على الأقل قبل ما تنشر الامتحان.',
        ],
        'attempt' => [
            'not_finished' => 'الطالب لسه ماخلّصش المحاولة دي.',
        ],
        'answer' => [
            'auto_graded' => 'السؤال ده بيتصحح تلقائي.',
        ],
        'invoice' => [
            'not_pending' => 'الفاتورة دي اتراجعت قبل كده.',
        ],
        'recharge_card' => [
            'used' => 'الكود ده اتستخدم — مينفعش يتعدّل أو يتحذف.',
        ],
    ],
];
