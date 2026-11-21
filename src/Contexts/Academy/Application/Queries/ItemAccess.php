<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Application\Queries;

use Src\Contexts\Academy\Application\Actions\RecordItemViewAction;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\SectionItemView;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Exams\Domain\Models\OnlinePassedExam;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * يقدر الطالب يفتح العنصر ده؟ بيرجّع سبب المنع (مفتاح ترجمة) أو null لو مسموح.
 *
 * الترتيب: منشور ← مشترك (أو معاينة مجانية) ← الامتحان القبلي اتنجح فيه ← عدد المشاهدات.
 */
final readonly class ItemAccess
{
    public function __construct(private BootcampAccess $access) {}

    public function denial(?Student $student, BootcampSectionItem $item): ?string
    {
        $section = $item->section;
        $bootcamp = $section?->bootcamp;

        if ($section === null || $bootcamp === null
            || $item->publish_status !== PublishStatus::Published
            || $section->publish_status !== PublishStatus::Published
            || ! $bootcamp->isPublished()) {
            return 'item_unavailable';
        }

        $preview = $item->is_free_preview || $section->is_free_preview;

        if ($student === null) {
            return $preview ? null : 'login_required';
        }

        if (! $preview && ! $this->access->has($student, $bootcamp)) {
            return 'not_enrolled';
        }

        $prerequisite = $item->prerequisite_exam_id ?? $section->prerequisite_exam_id;

        if ($prerequisite !== null && ! $this->passed($student, (int) $prerequisite)) {
            return 'prerequisite_exam';
        }

        if ($item->allowed_views_number !== null && $item->online_lesson_id !== null) {
            $view = SectionItemView::query()
                ->where('student_id', $student->getKey())
                ->where('bootcamp_section_item_id', $item->getKey())
                ->first(['views_count', 'viewed_at']);

            // نفس المشاهدة (ريفرش/رجوع خلال الساعة) مابتتحسبش — زي RecordItemViewAction بالظبط
            $sameView = $view !== null && $view->viewed_at->gte(now()->subMinutes(RecordItemViewAction::SAME_VIEW_MINUTES));

            if ($view !== null && $view->views_count >= $item->allowed_views_number && ! $sameView) {
                return 'views_exhausted';
            }
        }

        return null;
    }

    public function allows(?Student $student, BootcampSectionItem $item): bool
    {
        return $this->denial($student, $item) === null;
    }

    private function passed(Student $student, int $examId): bool
    {
        return OnlinePassedExam::query()
            ->with('exam')
            ->where('student_id', $student->getKey())
            ->where('online_exam_id', $examId)
            ->where('finished', true)
            ->get()
            ->contains(fn (OnlinePassedExam $attempt): bool => $attempt->passed());
    }
}
