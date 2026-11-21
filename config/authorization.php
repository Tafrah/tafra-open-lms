<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | الحارس (Guard)
    |--------------------------------------------------------------------------
    */
    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | فاصل المفتاح
    |--------------------------------------------------------------------------
    | الشكل النهائي للصلاحية: {action}.{resource}  مثال: view_any.users
    */
    'separator' => '.',

    /*
    |--------------------------------------------------------------------------
    | الأفعال القياسية لكل مورد
    |--------------------------------------------------------------------------
    */
    'default_actions' => [
        'view_any', 'view', 'create', 'update', 'delete',
        'delete_any', 'restore', 'force_delete', 'export',
    ],

    /*
    |--------------------------------------------------------------------------
    | الموارد
    |--------------------------------------------------------------------------
    | group   : مجموعة العرض في شاشة الأدوار (مفتاح ترجمة)
    | actions : لو null → default_actions. لو محددة → دي بس.
    | extra   : أفعال مخصصة إضافية
    */
    'resources' => [

        'users' => [
            'group' => 'identity',
            'actions' => null,
            'extra' => ['impersonate', 'reset_password', 'force_logout', 'manage_roles'],
        ],

        'roles' => [
            'group' => 'identity',
            'actions' => ['view_any', 'view', 'create', 'update', 'delete'],
        ],

        'tenants' => [
            'group' => 'tenancy',
            'actions' => null,
            'extra' => ['suspend', 'impersonate'],
        ],

        'announcements' => [
            'group' => 'content',
            'actions' => null,
            'extra' => ['publish', 'pin'],
        ],

        'media' => [
            'group' => 'content',
            'actions' => ['view_any', 'view', 'create', 'delete'],
            'extra' => ['download', 'change_disk'],
        ],

        'settings' => [
            'group' => 'system',
            'actions' => ['view_any', 'update'],
            'extra' => ['manage_storage', 'manage_mail', 'manage_appearance', 'manage_security', 'manage_notifications', 'manage_ai'],
        ],

        'activity_logs' => [
            'group' => 'system',
            'actions' => ['view_any', 'view'],
            'extra' => ['prune'],
        ],

        // ═══════════ المنصة التعليمية ═══════════
        'educational_levels' => ['group' => 'academy', 'actions' => null],
        'online_lessons' => ['group' => 'academy', 'actions' => null],
        'bootcamps' => ['group' => 'academy', 'actions' => null, 'extra' => ['publish']],
        'online_exams' => ['group' => 'exams', 'actions' => null, 'extra' => ['publish']],
        'exam_attempts' => [
            'group' => 'exams',
            'actions' => ['view_any', 'view', 'delete', 'export'],
            'extra' => ['grade'],
        ],
        'students' => [
            'group' => 'students',
            'actions' => null,
            'extra' => ['adjust_wallet', 'enroll', 'reset_password', 'block'],
        ],
        'enrollments' => [
            'group' => 'students',
            'actions' => ['view_any', 'view', 'create', 'delete', 'export'],
        ],
        'payment_methods' => ['group' => 'billing', 'actions' => null],
        'payment_invoices' => [
            'group' => 'billing',
            'actions' => ['view_any', 'view', 'export'],
            'extra' => ['approve', 'reject'],
        ],
        'money_transactions' => [
            'group' => 'billing',
            'actions' => ['view_any', 'view', 'export'],
        ],
        'recharge_cards' => [
            'group' => 'billing',
            'actions' => ['view_any', 'view', 'create', 'update', 'delete', 'export'],
            'extra' => ['print'],
        ],
        'coupons' => ['group' => 'billing', 'actions' => null],

        // تطبيقات الذكاء الاصطناعي المربوطة (docs/23) — المستخدم بيشوف ويلغي
        // روابطه هو من غير أي صلاحية؛ دي لمدير المؤسسة على روابط الكل
        'ai_connections' => [
            'group' => 'ai',
            'actions' => ['view_any', 'delete'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | صلاحيات الصفحات المخصصة (مش مرتبطة بمورد)
    |--------------------------------------------------------------------------
    | كل مفتاح هنا بيتحوّل لـ Gate معرّف — راجع docs/19-policies.md بند ٧.
    */
    'pages' => [
        'access.panel.admin' => 'system',
        'access.panel.central' => 'central',
        'access.dashboard' => 'system',
        'access.horizon' => 'system',
        'access.pulse' => 'system',
        'access.health' => 'system',
        'access.log_viewer' => 'system',
        'access.backups' => 'system',
        'access.system' => 'system',
        'access.tenancy' => 'tenancy',
        'access.organization_profile' => 'tenancy',
        'access.onboarding_analytics' => 'system',
        'access.notification_preferences' => 'system',
        'access.my_devices' => 'identity',
        'access.two_factor' => 'identity',
        // ربط Claude / ChatGPT بالحساب عن طريق MCP (docs/23)
        'access.ai' => 'ai',
        // نقل البيانات من/إلى طفرة (docs/product/plan.html#sync)
        'access.data_transfer' => 'system',
        // إعدادات الموقع العام (الواجهة، صورة المدرس، السوشيال)
        'access.site_settings' => 'system',
    ],

    /*
    |--------------------------------------------------------------------------
    | صلاحيات الودجتس
    |--------------------------------------------------------------------------
    */
    'widgets' => [
        'widget.stats_overview' => 'dashboard',
        'widget.users_chart' => 'dashboard',
        'widget.recent_activity' => 'dashboard',
        'widget.onboarding_checklist' => 'dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | الأدوار الافتراضية
    |--------------------------------------------------------------------------
    | أدوار **المؤسسة** — بتتربط بالمستخدم على فريق المؤسسة.
    | '*' = كل الصلاحيات. الأدوار دي بتتزامن مع كل تشغيل للأمر.
    */
    'roles' => [
        // ⚠️ مفيش `settings.*` ولا `access.health`: الإعدادات العامة (البريد،
        // التخزين، الأمان) وأدوات النظام بتاعة فريق التشغيل (docs/24 بند ٦)
        'admin' => [
            'users.*', 'roles.*', 'media.*', 'announcements.*',
            'educational_levels.*', 'online_lessons.*', 'bootcamps.*', 'online_exams.*',
            'exam_attempts.*', 'students.*', 'enrollments.*', 'payment_methods.*',
            'payment_invoices.*', 'money_transactions.*', 'recharge_cards.*', 'coupons.*',
            'access.data_transfer', 'access.site_settings',
            // مظهر المؤسسة وإشعاراتها بس — بتتكتب في `tenant_settings` مش في العام
            'manage_appearance.settings', 'manage_notifications.settings', 'manage_ai.settings',
            'view_any.activity_logs', 'view.activity_logs',
            'access.ai', 'ai_connections.*',
            'access.dashboard', 'access.system', 'access.organization_profile',
            'access.my_devices', 'access.two_factor', 'access.notification_preferences',
            'access.panel.admin',
            'widget.*',
        ],

        'editor' => [
            'view_any.users', 'view.users',
            'media.*',
            'view_any.announcements', 'view.announcements',
            'create.announcements', 'update.announcements', 'export.announcements',
            'access.dashboard', 'access.panel.admin', 'access.ai',
            'access.my_devices', 'access.two_factor', 'access.notification_preferences',
            'widget.stats_overview',
        ],

        // المساعد: بيراجع الإيصالات ويصحح المقالي ويتابع الطلبة — من غير فلوس ولا إعدادات
        'assistant' => [
            'view_any.students', 'view.students', 'create.students', 'update.students',
            'enroll.students', 'reset_password.students', 'block.students',
            'view_any.enrollments', 'view.enrollments', 'create.enrollments',
            'view_any.payment_invoices', 'view.payment_invoices', 'approve.payment_invoices', 'reject.payment_invoices',
            'view_any.exam_attempts', 'view.exam_attempts', 'grade.exam_attempts',
            'view_any.online_exams', 'view.online_exams',
            'view_any.bootcamps', 'view.bootcamps',
            'view_any.online_lessons', 'view.online_lessons',
            'view_any.educational_levels', 'view.educational_levels',
            'view_any.recharge_cards', 'view.recharge_cards',
            'access.dashboard', 'access.panel.admin',
            'access.my_devices', 'access.two_factor', 'access.notification_preferences',
            'widget.stats_overview',
        ],

        'viewer' => [
            'view_any.*', 'view.*',
            'access.dashboard', 'access.panel.admin', 'access.ai',
            'access.my_devices', 'access.two_factor', 'access.notification_preferences',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | أدوار فريق التشغيل (اللوحة المركزية /central)
    |--------------------------------------------------------------------------
    | بتتربط على الفريق `central_team_id` — مش على مؤسسة (docs/24 بند ٢).
    | تعريف الدور نفسه عام زي أدوار المؤسسة؛ الإسناد هو اللي مختلف.
    */
    'central_roles' => [
        'super_admin' => ['*'],

        'support' => [
            // صلاحيات الخطط (`view_any.plans`) بتتضاف مع سياق Subscriptions — مش قبله (docs/24 بند ٥)
            // `access.system` = منطقة سجل النشاط (ActivityLogPolicy::area) — من
            // غيرها `view_any.activity_logs` صلاحية ميتة. الإعدادات لسه 403:
            // مفيش ولا `manage_*` (docs/24 بند ٧)
            'access.panel.central', 'access.tenancy', 'access.system', 'access.health',
            'view_any.tenants', 'view.tenants', 'impersonate.tenants',
            'view_any.activity_logs', 'view.activity_logs',
            'access.two_factor', 'access.my_devices',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | الدور الخارق
    |--------------------------------------------------------------------------
    | بيتجاوز الصلاحيات عبر Gate::before — لكن مش قواعد السلامة (invariants).
    */
    'super_admin_role' => 'super_admin',

    /*
    | دور مدير المؤسسة — فريق التشغيل بيدخل المؤسسة بصفته (docs/24 بند ٤).
    */
    'tenant_admin_role' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | فريق السياق المركزي
    |--------------------------------------------------------------------------
    | spatie teams بيخلّي `tenant_id` في `model_has_roles` إلزامي وجزء من
    | المفتاح الأساسي — فالأدوار المركزية (لوحة /central) بتتربط بفريق
    | محجوز. أرقام المؤسسات بتبدأ من ١، فالصفر مابيتصادمش (docs/24 بند ٢).
    */
    'central_team_id' => 0,

    /*
    |--------------------------------------------------------------------------
    | حماية من الحذف
    |--------------------------------------------------------------------------
    | أدوار ممنوع حذفها أو تعديل صلاحياتها من الواجهة
    */
    'protected_roles' => ['super_admin'],
];
