<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

/**
 * كل مهمة مجدولة عليها `onOneServer()` (docs/13 بند ٣).
 *
 * لو التطبيق على أكتر من سيرفر، المهمة من غير القفل دي بتتنفّذ مرة
 * على كل سيرفر: نسختين احتياطيتين، إشعارين لكل مستخدم، تنظيف مرتين.
 * الشرط كان مكتوب في الوثيقة ومفيش حاجة بتفرضه — و`horizon:snapshot`
 * كان فعلاً ناقصه.
 */
it('كل مهمة مجدولة عليها onOneServer', function (): void {
    $violations = [];

    foreach (app(Schedule::class)->events() as $event) {
        if ($event->onOneServer) {
            continue;
        }

        $violations[] = $event->command ?? $event->description ?? 'closure';
    }

    expect($violations)->toBeEmpty("مهام من غير onOneServer():\n".implode("\n", $violations));
});

/**
 * `Schedule::call()` من غير `name()` بيرمي LogicException لما تضيف
 * `onOneServer()` — والاستثناء بيظهر وقت التشغيل مش وقت الكتابة.
 */
it('كل مهمة closure ليها اسم', function (): void {
    $violations = [];

    foreach (app(Schedule::class)->events() as $event) {
        if ($event->command !== null) {
            continue;
        }

        if (($event->description ?? '') === '') {
            $violations[] = $event->expression;
        }
    }

    expect($violations)->toBeEmpty("مهام closure من غير name():\n".implode("\n", $violations));
});
