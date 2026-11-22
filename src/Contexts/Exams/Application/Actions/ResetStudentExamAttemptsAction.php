<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Exams\Domain\Models\StudentExamAnswer;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * استثناء لطالب واحد: محاولاته في امتحان بتتمسح (بإجاباتها وصورها) فيقدر يعيده
 * من الأول بعدد المحاولات كامل. المسح نهائي — ده المقصود («يعيد كأنه أول مرة»).
 */
final readonly class ResetStudentExamAttemptsAction
{
    /** @return int عدد المحاولات اللي اتمسحت */
    public function handle(Student $student, int $examId, User $actor): int
    {
        $attempts = OnlinePassedExam::query()
            ->where('student_id', $student->getKey())
            ->where('online_exam_id', $examId)
            ->get();

        foreach ($attempts as $attempt) {
            Gate::forUser($actor)->authorize('reset', $attempt);
        }

        return DB::transaction(function () use ($student, $attempts): int {
            // نفس قفل StartAttemptAction — محاولة جديدة مابتتفتحش في نص المسح
            Student::query()->whereKey($student->getKey())->lockForUpdate()->first();

            // واحدة واحدة عشان صور المقالي (media) تتمسح مع الإجابة — الـ cascade في القاعدة مابيشوفهاش
            StudentExamAnswer::query()
                ->with('media')
                ->whereIn('online_passed_exam_id', $attempts->modelKeys())
                ->get()
                ->each(fn (StudentExamAnswer $answer) => $answer->delete());

            return OnlinePassedExam::query()->whereKey($attempts->modelKeys())->delete();
        });
    }
}
