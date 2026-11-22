<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Infrastructure\Notifications;

/** المدرس خلّص تصحيح المقالي — النتيجة النهائية ظهرت. */
final class AttemptGradedNotification extends StudentNotification
{
    public function __construct(
        public readonly int $attemptId,
        public readonly string $exam,
        public readonly float $percentage,
    ) {
        parent::__construct();
    }

    public static function key(): string
    {
        return 'student_attempt_graded';
    }

    protected function kind(): string
    {
        return 'attempt_graded';
    }

    protected function params(): array
    {
        return ['exam' => $this->exam, 'percentage' => $this->percentage];
    }

    protected function link(): array
    {
        return ['site.attempts.result', ['attempt' => $this->attemptId]];
    }
}
