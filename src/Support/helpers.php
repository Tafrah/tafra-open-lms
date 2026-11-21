<?php

declare(strict_types=1);

// دوال مساعدة عامة. مقسّمة بتعليقات — مفيش helpers.php عملاق من غير تنظيم.
// راجع docs/18-conventions.md بند ٧.

use Carbon\CarbonInterface;
use Src\Support\Infrastructure\Authorization\PermissionBuilder;

// ═════════════════════════════════════════════════════════════════════════════
// التفويض
// ═════════════════════════════════════════════════════════════════════════════

if (! function_exists('permission_label')) {
    /** الاسم المعروض لصلاحية — مترجم، ومن غير مفاتيح ترجمة مكشوفة. */
    function permission_label(string $permission): string
    {
        $separator = (string) config('authorization.separator', '.');

        if (! str_contains($permission, $separator)) {
            return $permission;
        }

        [$left, $right] = explode($separator, $permission, 2);

        // مفاتيح الصفحات والودجتس فيها نقط (`access.panel.admin`)، والـ
        // dot-notation بتاعة __() بتفسّرها كتداخل — فبنجيب المصفوفة كلها
        // ونفهرس عليها مباشرة.
        foreach (['pages', 'widgets'] as $group) {
            $labels = trans("authorization.{$group}");

            if (is_array($labels) && array_key_exists($permission, $labels)) {
                return (string) $labels[$permission];
            }
        }

        return __("authorization.actions.{$left}").' — '.__("authorization.resources.{$right}");
    }
}

if (! function_exists('permission_builder')) {
    function permission_builder(): PermissionBuilder
    {
        return app(PermissionBuilder::class);
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// المسارات
// ═════════════════════════════════════════════════════════════════════════════

if (! function_exists('src_path')) {
    function src_path(string $path = ''): string
    {
        return base_path('src'.($path !== '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// الأرقام والتواريخ (docs/10-i18n.md بند ٦)
// ═════════════════════════════════════════════════════════════════════════════

if (! function_exists('fc_number')) {
    /** أرقام لاتينية في الواجهة دايماً — حتى بالعربي. */
    function fc_number(int|float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, '.', ',');
    }
}

if (! function_exists('fc_currency')) {
    function fc_currency(int|float $amount, ?string $currency = null): string
    {
        $currency ??= (string) config('app.currency', 'EGP');

        return __("common.currency.{$currency}", ['amount' => fc_number($amount, 2)]);
    }
}

if (! function_exists('fc_date')) {
    function fc_date(?CarbonInterface $date, string $format = 'd M Y'): string
    {
        return $date?->locale(app()->getLocale())->translatedFormat($format) ?? '—';
    }
}
