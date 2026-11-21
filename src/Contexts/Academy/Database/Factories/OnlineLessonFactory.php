<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Academy\Domain\Enums\LessonType;
use Src\Contexts\Academy\Domain\Models\OnlineLesson;

/** @extends Factory<OnlineLesson> */
final class OnlineLessonFactory extends Factory
{
    protected $model = OnlineLesson::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(4),
            'type' => LessonType::YoutubeVideo,
            'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_in_seconds' => 600,
        ];
    }

    public function text(): self
    {
        return $this->state(fn (): array => [
            'type' => LessonType::Text,
            'external_url' => null,
            'text' => '<p>'.$this->faker->paragraph().'</p>',
        ]);
    }
}
