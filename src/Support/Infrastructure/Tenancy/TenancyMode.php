<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Tenancy;

/**
 * وضع تعدد المستأجرين الحالي — مكان واحد للسؤال بدل `config()` متفرّقة.
 */
final class TenancyMode
{
    public const SINGLE = 'single';

    public const MULTI = 'multi';

    public static function current(): string
    {
        return (string) config('tenancy.mode', self::SINGLE);
    }

    public static function isMulti(): bool
    {
        return self::current() === self::MULTI;
    }

    /** في single، جداول المستأجرين والجداول المركزية في نفس القاعدة. */
    public static function sharesCentralDatabase(): bool
    {
        return ! self::isMulti();
    }

    /**
     * اسم الاتصال المركزي — لأي استعلام خام (`DB::table`) على جدول مركزي.
     *
     * الموديلات المركزية بتستخدم `CentralConnection`. الاستعلام الخام
     * مالوش موديل، فبياخد الاتصال الافتراضي — واللي في multi بيبقى قاعدة
     * المؤسسة (docs/03 بند ٣).
     */
    public static function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection');
    }

    public static function isValid(string $mode): bool
    {
        return in_array($mode, (array) config('tenancy.modes', [self::SINGLE, self::MULTI]), true);
    }
}
