<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Contracts;

/**
 * خطوة أونبوردنج.
 *
 * القاعدة الحاكمة: `isComplete()` بتقرا **الحالة الحقيقية** من النظام،
 * مش علامة يدوية المستخدم ضغط عليها. لو حد حذف اللوجو، الخطوة ترجع
 * غير مكتملة (docs/15 بند ٤).
 */
interface OnboardingStep
{
    public static function key(): string;

    public function label(): string;

    public function description(): string;

    public function icon(): string;

    /** هل الخطوة دي مكتملة فعلاً؟ */
    public function isComplete(): bool;

    /** هل هي إلزامية للمتابعة؟ */
    public function isRequired(): bool;

    /** لينك الشاشة اللي بتكمّل الخطوة */
    public function url(): ?string;

    /** القدرة المطلوبة — لو مالوش، الخطوة مش بتظهر له */
    public function ability(): ?string;

    /** ترتيب العرض */
    public function sort(): int;

    /**
     * اللوحة اللي الخطوة بتتعمل فيها (`admin` أو `central`).
     * معالج وقائمة كل لوحة بيعرضوا خطواتها بس — مدير مؤسسة مالوش دعوة
     * بخادم البريد، وفريق التشغيل مالوش دعوة بدعوة فريق مؤسسة.
     */
    public function panel(): string;
}
