<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Application\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\SectionItemView;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * الطالب فتح درس: مشاهدة جديدة (لو آخر مرة من أكتر من ساعة — الريفرش مايتحسبش).
 */
final readonly class RecordItemViewAction
{
    public const SAME_VIEW_MINUTES = 60;

    public function handle(Student $student, BootcampSectionItem $item): SectionItemView
    {
        try {
            return $this->record($student, $item);
        } catch (UniqueConstraintViolationException) {
            // فتحتين في نفس اللحظة لأول مرة — التانية بتلاقي الصف اتعمل
            return $this->record($student, $item);
        }
    }

    private function record(Student $student, BootcampSectionItem $item): SectionItemView
    {
        return DB::transaction(function () use ($student, $item): SectionItemView {
            $view = SectionItemView::query()
                ->where('student_id', $student->getKey())
                ->where('bootcamp_section_item_id', $item->getKey())
                ->lockForUpdate()
                ->first();

            if ($view === null) {
                return SectionItemView::query()->create([
                    'student_id' => $student->getKey(),
                    'bootcamp_section_item_id' => $item->getKey(),
                    'views_count' => 1,
                    'viewed_at' => now(),
                ]);
            }

            if ($view->viewed_at->lt(now()->subMinutes(self::SAME_VIEW_MINUTES))) {
                $view->views_count++;
            }

            $view->viewed_at = now();
            $view->save();

            return $view;
        });
    }

    public function complete(Student $student, BootcampSectionItem $item): SectionItemView
    {
        $view = $this->handle($student, $item);

        if (! $view->is_completed) {
            $view->forceFill(['is_completed' => true, 'completed_at' => now()])->save();
        }

        return $view;
    }
}
