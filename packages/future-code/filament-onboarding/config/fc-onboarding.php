<?php

declare(strict_types=1);

use Filament\Auth\Pages\EditProfile;
use Filament\Pages\Dashboard;
use FutureCode\Onboarding\Pages\OnboardingAnalytics;
use FutureCode\Onboarding\Pages\SetupWizard;

return [

    /*
    | الاتصال اللي بيتخزّن فيه التقدّم. في تطبيق متعدد القواعد لازم يكون
    | المركزي — null = الاتصال الافتراضي.
    */
    'connection' => env('DB_CONNECTION'),

    'enabled' => (bool) env('ONBOARDING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | معالج الإعداد
    |--------------------------------------------------------------------------
    | skippable = false معناه إن المستخدم مايقدرش يدخل اللوحة قبل ما
    | يخلّص الخطوات الإلزامية.
    */
    'wizard' => [
        'enabled' => true,
        'slug' => 'setup',
        'skippable' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | الخطوات
    |--------------------------------------------------------------------------
    | بتتكتشف لوحدها: كل `Application/Onboarding/*Step.php` في السياقات
    | (`docs/15` بند ١٠). المصفوفة دي للإضافة من برا السياقات بس.
    | الترتيب من `sort()`، واللوحة من `panel()`.
    */
    'steps' => [],

    /** كلاسات خطوات/جولات مكتشفة ومش عايزينها في المشروع ده */
    'exclude' => [],

    /*
    |--------------------------------------------------------------------------
    | قائمة المهام
    |--------------------------------------------------------------------------
    */
    'checklist' => [
        'enabled' => true,
        'widget_sort' => -10,
        'hide_when_complete' => true,
        'dismissible' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | الجولات الإرشادية
    |--------------------------------------------------------------------------
    | بتتكتشف لوحدها: كل `Presentation/Tours/*Tour.php` في السياقات.
    */
    'tours' => [
        'enabled' => true,
        'auto_start' => true,
        'show_replay' => true,

        'registered' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | شاشات معفية من الجولة — كل إعفاء بسبب مترجم
    |--------------------------------------------------------------------------
    | `OnboardingCoverageTest` بيفشل على أي شاشة من غير جولة، وعلى أي إعفاء
    | سببه مش مترجم بكل اللغات. «معفية» لازم تبقى قرار مكتوب، مش نسيان.
    */
    'exempt' => [
        SetupWizard::class => 'fc-onboarding::onboarding.exempt.wizard',
        OnboardingAnalytics::class => 'fc-onboarding::onboarding.exempt.analytics',
        Dashboard::class => 'fc-onboarding::onboarding.exempt.dashboard',
        Src\Support\Presentation\Filament\Pages\Dashboard::class => 'fc-onboarding::onboarding.exempt.dashboard',
        EditProfile::class => 'fc-onboarding::onboarding.exempt.profile',
    ],

    /*
    |--------------------------------------------------------------------------
    | صفحة المقاييس
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'enabled' => true,
        'ability' => 'access.onboarding_analytics',
    ],
];
