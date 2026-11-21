<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Illuminate\Support\Facades\Gate;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Identity\Domain\Models\User;

final readonly class PublishExamAction
{
    public function handle(OnlineExam $exam, User $actor): OnlineExam
    {
        Gate::forUser($actor)->authorize('publish', $exam);

        $exam->update([
            'published' => true,
            'questions_number' => $exam->questions()->count(),
        ]);

        return $exam;
    }

    public function unpublish(OnlineExam $exam, User $actor): OnlineExam
    {
        Gate::forUser($actor)->authorize('update', $exam);

        $exam->update(['published' => false]);

        return $exam;
    }
}
