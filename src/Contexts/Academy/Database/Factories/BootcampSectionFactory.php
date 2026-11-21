<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\Bootcamp;
use Src\Contexts\Academy\Domain\Models\BootcampSection;

/** @extends Factory<BootcampSection> */
final class BootcampSectionFactory extends Factory
{
    protected $model = BootcampSection::class;

    public function definition(): array
    {
        return [
            'bootcamp_id' => Bootcamp::factory(),
            'name' => $this->faker->sentence(2),
            'order' => 1,
            'publish_status' => PublishStatus::Published,
        ];
    }
}
