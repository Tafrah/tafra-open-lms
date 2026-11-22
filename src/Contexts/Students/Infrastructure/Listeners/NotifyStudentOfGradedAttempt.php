<?php

declare(strict_types=1);

namespace Src\Contexts\Students\Infrastructure\Listeners;

use Src\Contexts\Exams\Domain\Events\AttemptGraded;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Students\Infrastructure\Notifications\AttemptGradedNotification;

/** Exams → Students عبر الحدث: النتيجة النهائية بعد تصحيح المقالي. */
final class NotifyStudentOfGradedAttempt
{
    public function handle(AttemptGraded $event): void
    {
        $attempt = OnlinePassedExam::query()
            ->with(['student', 'exam:id,name'])
            ->find($event->attemptId);

        $student = $attempt?->student;

        if ($attempt === null || $student === null) {
            return;
        }

        $exam = $attempt->exam instanceof OnlineExam ? $attempt->exam : null;

        $student->notifyNow(new AttemptGradedNotification(
            attemptId: (int) $attempt->getKey(),
            exam: (string) ($exam === null ? '' : $exam->name),
            percentage: (float) $attempt->student_percentage,
        ));
    }
}
