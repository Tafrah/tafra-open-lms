<?php

declare(strict_types=1);

namespace FutureCode\Onboarding\Tours;

/**
 * جولة إرشادية على شاشة (أو مجموعة شاشات بنفس الشكل).
 *
 * قواعد ثابتة (docs/15 بند ٥):
 *  · ٥ خطوات كحد أقصى — أكتر من كده محدش بيكمّل
 *  · الخطوات بتحترم الصلاحيات وبتتخطى بصمت
 *  · الحالة في قاعدة البيانات مش localStorage
 */
abstract class Tour
{
    public const MAX_STEPS = 5;

    /** مفتاح فريد للجولة */
    abstract public static function key(): string;

    /** الصفحة اللي الجولة بتشتغل عليها */
    abstract public static function page(): string;

    /** @return array<int, TourStep> */
    abstract public function steps(): array;

    /**
     * كل الصفحات اللي الجولة بتغطيها — مثلاً تبويبات الإعدادات كلها نفس الشكل.
     *
     * @return list<string>
     */
    public static function pages(): array
    {
        return [static::page()];
    }

    /**
     * اللوحات اللي الجولة بتظهر فيها — صفحة مشتركة بين لوحتين ليها جولة واحدة.
     *
     * @return list<string>
     */
    public static function panels(): array
    {
        return ['admin'];
    }

    /**
     * الخطوات اللي هتتعرض فعلاً — بعد فلترة الصلاحيات وحد الخمسة.
     *
     * @return array<int, array<string, mixed>>
     */
    public function payload(): array
    {
        return collect($this->steps())
            ->filter(static fn (TourStep $step): bool => $step->isVisible())
            ->take(self::MAX_STEPS)
            ->map(static fn (TourStep $step): array => $step->toArray())
            ->values()
            ->all();
    }

    /** خطوة نصوصها من مفتاح ترجمة واحد: `{$prefix}.{$key}.title` و`.body`. */
    protected static function step(string $prefix, string $key, string $element, string $placement = 'bottom'): TourStep
    {
        return TourStep::make($key)
            ->element($element)
            ->title(__("{$prefix}.{$key}.title"))
            ->body(__("{$prefix}.{$key}.body"))
            ->placement($placement);
    }
}
