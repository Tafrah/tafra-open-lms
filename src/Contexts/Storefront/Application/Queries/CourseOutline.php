<?php

declare(strict_types=1);

namespace Src\Contexts\Storefront\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Src\Contexts\Academy\Application\Queries\ItemAccess;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\SectionItemView;
use Src\Contexts\Students\Domain\Models\Student;

/**
 * محتوى الكورس (أجزاء ← عناصر ← درس/امتحان) في ٣ استعلامات بالظبط،
 * والعلاقات العكسية (item→section→bootcamp) متربطة في الذاكرة عشان
 * `ItemAccess` مايرجعش يسأل القاعدة لكل عنصر.
 */
final readonly class CourseOutline
{
    public function __construct(private ItemAccess $access) {}

    /** @return Collection<int, BootcampSection> */
    public function sections(Bootcamp $bootcamp): Collection
    {
        $sections = $bootcamp->sections()
            ->where('publish_status', PublishStatus::Published)
            ->with(['items' => static function (Relation $query): void {
                $query->where('publish_status', PublishStatus::Published)->with(['lesson', 'exam']);
            }])
            ->get();

        foreach ($sections as $section) {
            $section->setRelation('bootcamp', $bootcamp);

            foreach ($section->items as $item) {
                $item->setRelation('section', $section);
            }
        }

        return $sections;
    }

    /**
     * @param Collection<int, BootcampSection> $sections
     * @return list<BootcampSectionItem> العناصر بالترتيب (للسابق/التالي)
     */
    public function flatItems(Collection $sections): array
    {
        $items = [];

        foreach ($sections as $section) {
            foreach ($section->items as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * حالة كل عنصر للطالب: سبب القفل (أو null) وخلّصه ولا لأ.
     *
     * @param Collection<int, BootcampSection> $sections
     * @return array<int, array{denial: string|null, completed: bool}>
     */
    public function states(Collection $sections, ?Student $student): array
    {
        $items = $this->flatItems($sections);
        $completed = [];

        if ($student !== null && $items !== []) {
            $completed = SectionItemView::query()
                ->where('student_id', $student->getKey())
                ->whereIn('bootcamp_section_item_id', array_map(fn (BootcampSectionItem $i): int => (int) $i->getKey(), $items))
                ->where('is_completed', true)
                ->pluck('bootcamp_section_item_id')
                ->map(fn ($id): int => (int) $id)
                ->flip()
                ->all();
        }

        $states = [];

        foreach ($items as $item) {
            $states[(int) $item->getKey()] = [
                'denial' => $this->access->denial($student, $item),
                'completed' => array_key_exists((int) $item->getKey(), $completed),
            ];
        }

        return $states;
    }

    /** @param Collection<int, BootcampSection> $sections */
    public function countOf(Collection $sections, SectionItemType $type): int
    {
        return (int) $sections->sum(fn (BootcampSection $s): int => $s->items->where('type', $type)->count());
    }

    /** @param Collection<int, BootcampSection> $sections */
    public function durationSeconds(Collection $sections): int
    {
        return (int) $sections->sum(fn (BootcampSection $s): int => (int) $s->items->sum(fn (BootcampSectionItem $i): int => (int) data_get($i, 'lesson.duration_in_seconds', 0)));
    }
}
