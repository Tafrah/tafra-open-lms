<?php

declare(strict_types=1);
use Src\Contexts\Academy\Presentation\Filament\Resources\BootcampSectionResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\EnrollmentResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\MoneyTransactionResource;
use Src\Contexts\Billing\Presentation\Filament\Resources\RechargeCardGroupResource;
use Src\Contexts\Exams\Presentation\Filament\Resources\OnlinePassedExamResource;

/*
|--------------------------------------------------------------------------
| وصول الذكاء الاصطناعي — خادم MCP (docs/23)
|--------------------------------------------------------------------------
| الـ AI مستخدم عادي بصلاحيات أقل — مش باب خلفي. كل قيمة هنا قرار
| تشغيلي، مش ثابت في الكود: المشروع اللي مبني على القالب بيغيّرها من غير
| ما يلمس الخادم.
*/

return [

    /*
    | المسار العام للخادم. العميل (Claude / ChatGPT) بيتوصّل بالرابط ده،
    | والمؤسسة بتتحدد في شاشة الموافقة — مش في الرابط ولا في أي مدخل.
    */
    'path' => env('MCP_PATH', 'mcp'),

    /*
    | مفتاح إيقاف على مستوى البيئة — فوق مفتاح الإعدادات (`SecuritySettings::mcp_enabled`)
    | ومفتاح المؤسسة (`ai.mcp_enabled`). الثلاثة لازم يكونوا مفتوحين.
    */
    'enabled' => (bool) env('MCP_ENABLED', true),

    /*
    | فريق التشغيل (/central) عن طريق AI. مقفول افتراضياً: صلاحيات المدير
    | العام أوسع من أي مؤسسة، وفتحها قرار لازم يتاخد عن وعي.
    */
    'central_enabled' => (bool) env('MCP_CENTRAL_ENABLED', false),

    'server' => [
        'name' => env('MCP_SERVER_NAME', 'future-code-admin'),
        'version' => '1.0.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth (Passport)
    |--------------------------------------------------------------------------
    | Claude وChatGPT بيسجّلوا نفسهم ديناميكياً (RFC 7591) وبيستخدموا PKCE.
    | التوكن قصير العمر، والتجديد بـ refresh token بيتلف مع كل استخدام —
    | والإلغاء بيقفل الاتنين. روابط الرجوع المسموحة في `config/mcp.php`.
    |
    | النطاق الوحيد اللي `laravel/mcp` بيدعمه هو `mcp:use` — فالقراءة والكتابة
    | **مش** نطاقات OAuth: المستخدم بيختار المستوى في شاشة الموافقة، وبيتحفظ
    | على الربط نفسه (`ai_connections.access`). الصلاحية الفعلية =
    | صلاحيات المستخدم ∩ مستوى الربط ∩ مفتاح المؤسسة.
    */
    'oauth' => [
        'access_token_minutes' => (int) env('MCP_ACCESS_TOKEN_MINUTES', 60),
        'refresh_token_days' => (int) env('MCP_REFRESH_TOKEN_DAYS', 30),
        'registrations_per_minute' => (int) env('MCP_REGISTRATIONS_PER_MINUTE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | الحدود
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'per_minute' => (int) env('MCP_RATE_LIMIT_PER_MINUTE', 60),
        'writes_per_minute' => (int) env('MCP_WRITE_RATE_LIMIT_PER_MINUTE', 10),
    ],

    'pagination' => [
        'default' => 20,
        'max' => 50,
    ],

    // رد ضخم = تكلفة أعلى ومساحة أكبر لحقن التعليمات
    'max_response_bytes' => (int) env('MCP_MAX_RESPONSE_BYTES', 60_000),

    // أقصى طول لأي نص بيرجع للنموذج (بعد تحويل HTML لنص عادي)
    'max_text_length' => 4_000,

    // تحديث `last_used_at` مرة كل كام ثانية — مش مع كل نداء
    'touch_interval_seconds' => 60,

    /*
    |--------------------------------------------------------------------------
    | الممنوع — مش هيتعمل حتى لو حد أعلنه (docs/23 بند ٥)
    |--------------------------------------------------------------------------
    | قدرات Policy ممنوع تتحوّل لأداة، في أي مورد. `McpRegistry` بيرفض التسجيل،
    | و`McpCoverageTest` بيمسكها قبل الدمج.
    */
    'forbidden_abilities' => [
        'impersonate',
        'manageRoles',
        'resetPassword',
        'forceLogout',
        'prune',
        'export',
        'changeDisk',
    ],

    /*
    | موارد للقراءة بس عن طريق AI — الكتابة فيها تغيير صلاحيات أو إعدادات عامة.
    | بالـ resource name بتاع الـ Policy.
    */
    'read_only_resources' => [
        'roles',
        'settings',
        'activity_logs',
        'users',
    ],

    /*
    | موارد Filament من غير أدوات MCP — كل واحد بسبب مترجم بكل اللغات.
    | المفتاح كلاس المورد، والقيمة مفتاح ترجمة السبب.
    */
    'exempt' => [
        // 'Src\Contexts\X\Presentation\Filament\Resources\YResource' => 'x::mcp.exempt.reason',
        BootcampSectionResource::class => 'academy::academy.mcp.exempt.sections',
        OnlinePassedExamResource::class => 'exams::exams.mcp.exempt.attempts',
        RechargeCardGroupResource::class => 'billing::billing.mcp.exempt.cards',
        MoneyTransactionResource::class => 'billing::billing.mcp.exempt.transactions',
        EnrollmentResource::class => 'billing::billing.mcp.exempt.enrollments',
    ],

    /*
    | حقول بتتشال من مدخلات سجل النشاط قبل الحفظ — فوق `logging.redact`.
    */
    'redact' => ['body', 'content'],
];
