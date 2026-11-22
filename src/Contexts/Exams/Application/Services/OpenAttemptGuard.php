<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Services;

use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * الشروط المشتركة لأي كتابة في محاولة مفتوحة (إجابة نصية/اختيار أو صورة):
 * المحاولة بتاعته، لسه ماتسلّمتش، الوقت مخلصش، والسؤال جوه المحاولة.
 */
final readonly class OpenAttemptGuard
{
    /** سماحية بسيطة لفرق التوقيت بين الموبايل والسيرفر */
    public const GRACE_SECONDS = 20;

    public function assertWritable(Student $student, OnlinePassedExam $attempt, int $questionId): void
    {
        throw_unless((int) $attempt->student_id === (int) $student->getKey(), ExamsException::because('attempt_not_yours'));
        throw_if($attempt->finished, ExamsException::because('attempt_finished'));
        throw_if(
            $attempt->deadline_at !== null && $attempt->deadline_at->copy()->addSeconds(self::GRACE_SECONDS)->isPast(),
            ExamsException::because('time_up'),
        );
        throw_unless(in_array($questionId, array_map('intval', (array) $attempt->question_order), true), ExamsException::because('question_not_in_exam'));
    }
}
