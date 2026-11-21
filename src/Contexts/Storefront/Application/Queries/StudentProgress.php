<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\SectionItemView;
use Src\Contexts\Billing\Application\Queries\BootcampAccess;
use Src\Contexts\Students\Domain\Models\Student;

/** كورسات الطالب ونسبة التقدم في كل واحد — عدد ثابت من الاستعلامات مهما زاد عدد الكورسات. */
final readonly class StudentProgress
{
    public function __construct(private BootcampAccess $access) {}

    /** @return Collection<int, Bootcamp> كل كورس عليه `progress` (٠–١٠٠) */
    public function courses(Student $student): Collection
    {
        $ids = $this->access->bootcampIds($student);

        if ($ids === []) {
            return new Collection;
        }

        $courses = Bootcamp::query()->published()->with(['level', 'media'])->whereIn('id', $ids)->latest('id')->get();

        $items = BootcampSectionItem::query()
            ->where('publish_status', PublishStatus::Published)
            ->whereHas('section', fn (Builder $q) => $q->whereIn('bootcamp_id', $ids)->where('publish_status', PublishStatus::Published))
            ->with('section:id,bootcamp_id')
            ->get(['id', 'bootcamp_section_id']);

        $done = SectionItemView::query()
            ->where('student_id', $student->getKey())
            ->where('is_completed', true)
            ->whereIn('bootcamp_section_item_id', $items->modelKeys())
            ->pluck('bootcamp_section_item_id')
            ->map(fn ($id): int => (int) $id)
            ->flip();

        $byCourse = $items->groupBy(fn (BootcampSectionItem $i): int => (int) $i->section?->bootcamp_id);

        foreach ($courses as $course) {
            $courseItems = $byCourse->get((int) $course->getKey(), collect());
            $total = $courseItems->count();
            $completed = $courseItems->filter(fn (BootcampSectionItem $i): bool => $done->has((int) $i->getKey()))->count();

            $course->setAttribute('progress', $total > 0 ? (int) round($completed / $total * 100) : 0);
        }

        return $courses;
    }
}
