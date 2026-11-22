<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * محاولات الطلبة. التصحيح للمحاولات اللي فيها مقالي مستني.
 */
final class OnlinePassedExamPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'exam_attempts';
    }

    /** الزرار في شاشة الطالب (قبل ما يختار امتحان) — صلاحية بس */
    public function resetAny(User $user): Response
    {
        return $this->decide()->permission($user, $this, 'reset')->response();
    }

    /** مسح المحاولة عشان الطالب يعيد الامتحان */
    public function reset(User $user, OnlinePassedExam $record): Response
    {
        return $this->owned($user, $record, 'reset')->response();
    }

    public function grade(User $user, OnlinePassedExam $record): Response
    {
        return $this->owned($user, $record, 'grade')
            ->rule($record->finished, 'attempt.not_finished')
            ->response();
    }
}
