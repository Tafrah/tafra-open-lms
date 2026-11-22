<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| الموقع العام للطلبة (src/Contexts/Storefront)
|--------------------------------------------------------------------------
| القيم الافتراضية هنا بس — المدرس بيغيّرها من «إعدادات الموقع» في اللوحة
| (`tenant_settings`, المجموعة `site`).
*/

return [

    // شارة «مطوّر بواسطة طفرة» في فوتر كل صفحة — تسويق، مش إعداد للمدرس
    'tafra_url' => 'https://tafra.academy',

    'defaults' => [
        'primary_color' => '#3B82F6',
        'secondary_color' => '#6B7280',
        'font_family' => 'Almarai',
    ],

    // الخطوط المتاحة ← معامل Google Fonts
    'fonts' => [
        'Almarai' => 'Almarai:wght@300;400;700;800',
        'Cairo' => 'Cairo:wght@400;600;700;800',
        'Tajawal' => 'Tajawal:wght@400;500;700;800',
        'IBM Plex Sans Arabic' => 'IBM+Plex+Sans+Arabic:wght@400;500;600;700',
    ],

    'limits' => [
        // محاولات دخول غلط لكل (موبايل + IP) في الدقيقة
        'login_attempts' => 5,
        // أكواد شحن غلط لكل طالب في `redeem_decay_seconds` (١٥ دقيقة — docs/product/plan.html)
        'redeem_attempts' => 5,
        'redeem_decay_seconds' => 900,
        // أي POST من الموقع (تسجيل، إيصال، كوبون...) لكل IP في الدقيقة
        'forms_per_minute' => 30,
    ],

    // أقصى حجم لصورة الإيصال بالكيلوبايت
    'receipt_max_kb' => 5120,

    // صلاحية رابط ملف الـ PDF الموقّع بالدقايق
    'pdf_link_minutes' => 30,

    // كاش أرقام الصفحة الرئيسية بالثواني
    'stats_cache_seconds' => 300,

    'per_page' => 12,
];
