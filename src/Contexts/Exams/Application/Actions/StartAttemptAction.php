<?php

declare(strict_types=1);

namespace Src\Contexts\Exams\Application\Actions;

use Illuminate\Support\Facades\DB;
use Src\Contexts\Exams\Domain\Enums\GradingStatus;
use Src\Contexts\Exams\Domain\Exceptions\ExamsException;
use Src\Contexts\Exams\Domain\Models\OnlineExam;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * يبدأ محاولة جديدة (أو يكمّل محاولة مفتوحة لسه وقتها ماخلصش).
 * التحقق من إن الطالب مسموحله بالامتحان (اشتراك/معاينة) بيحصل قبلها في ItemAccess.
 */
final readonly class StartAttemptAction
{
    public function __construct(private SubmitAttemptAction $submit) {}

    public function handle(Student $student, OnlineExam $exam, ?int $sectionItemId = null): OnlinePassedExam
    {
        throw_unless($exam->isOpenNow(), ExamsException::because('exam_closed'));

        return DB::transaction(function () use ($student, $exam, $sectionItemId): OnlinePassedExam {
            Student::query()->whereKey($student->getKey())->lockForUpdate()->first();

            $open = OnlinePassedExam::query()
                ->where('student_id', $student->getKey())
                ->where('online_exam_id', $exam->getKey())
                ->where('finished', false)
                ->latest('id')
                ->first();

            if ($open !== null) {
                if (! $open->isExpired()) {
                    return $open;
                }

                $this->submit->handle($open);
            }

            $used = OnlinePassedExam::query()
                ->where('student_id', $student->getKey())
                ->where('online_exam_id', $exam->getKey())
                ->count();

            throw_if(
                $exam->allowed_chances !== null && $used >= $exam->allowed_chances,
                ExamsException::because('no_chances_left'),
            );

            $questionIds = $exam->questions()->pluck('id')->map(fn ($id): int => (int) $id)->all();
            throw_if($questionIds === [], ExamsException::because('exam_empty'));

            if ($exam->random_questions) {
                shuffle($questionIds);
            }

            $deadline = $exam->duration_in_seconds !== null ? now()->addSeconds($exam->duration_in_seconds) : null;

            if ($exam->end_at !== null && ($deadline === null || $exam->end_at->lt($deadline))) {
                $deadline = $exam->end_at->copy();
            }

            return OnlinePassedExam::query()->create([
                'student_id' => $student->getKey(),
                'online_exam_id' => $exam->getKey(),
                'bootcamp_section_item_id' => $sectionItemId,
                'current_student_chance' => $used + 1,
                'max_total_mark' => $exam->totalMark(),
                'finished' => false,
                'grading_status' => GradingStatus::Graded,
                'question_order' => $questionIds,
                'start_at' => now(),
                'deadline_at' => $deadline,
            ]);
        });
    }
}
