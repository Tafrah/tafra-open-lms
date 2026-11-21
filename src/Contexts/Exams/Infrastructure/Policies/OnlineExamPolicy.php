<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Infrastructure\Policies;

use Illuminate\Auth\Access\Response;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Authorization\StandardPolicy;

/**
 * الامتحانات.
 */
final class OnlineExamPolicy extends StandardPolicy
{
    protected function resource(): string
    {
        return 'online_exams';
    }

    public function publish(User $user, OnlineExam $record): Response
    {
        return $this->owned($user, $record, 'publish')
            ->rule(! $record->trashed(), 'record_trashed')
            ->rule(! $record->published, 'exam.already_published')
            ->ruleUsing(fn (): bool => $record->questions()->exists(), 'exam.no_questions')
            ->response();
    }
}
