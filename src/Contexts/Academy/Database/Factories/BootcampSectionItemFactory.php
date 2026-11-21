<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Enums\SectionItemType;
use Src\Contexts\Academy\Domain\Models\BootcampSection;
use Src\Contexts\Academy\Domain\Models\BootcampSectionItem;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;

/** @extends Factory<BootcampSectionItem> */
final class BootcampSectionItemFactory extends Factory
{
    protected $model = BootcampSectionItem::class;

    public function definition(): array
    {
        return [
            'bootcamp_section_id' => BootcampSection::factory(),
            'name' => $this->faker->sentence(3),
            'order' => 1,
            'type' => SectionItemType::Lesson,
            'online_lesson_id' => OnlineLesson::factory(),
            'publish_status' => PublishStatus::Published,
        ];
    }
}
