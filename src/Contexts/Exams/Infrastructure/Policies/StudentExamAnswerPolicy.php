<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * إجابات الطلبة.
 */
final class StudentExamAnswerPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'exam_attempts';
    }

    public function grade(User $user, StudentExamAnswer $record): Response
    {
        return $this->owned($user, $record, 'grade')
            ->rule($record->grading_status !== GradingStatus::Auto, 'answer.auto_graded')
            ->response();
    }
}
