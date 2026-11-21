<?php

declare(strict_types=1);

namespace Src\Contexts\Content\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Contexts\Content\Domain\Enums\AnnouncementSeverity;
use Src\Contexts\Content\Domain\Enums\AnnouncementStatus;
use Src\Contexts\Content\Domain\Models\Announcement;
use Src\Contexts\Identity\Domain\Models\User;

/** @extends Factory<Announcement> */
final class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(5);

        return [
            'author_id' => User::query()->value('id') ?? User::factory(),
            'title' => ['ar' => $title, 'en' => $title],
            'body' => [
                'ar' => '<p>'.$this->faker->paragraph().'</p>',
                'en' => '<p>'.$this->faker->paragraph().'</p>',
            ],
            'status' => AnnouncementStatus::Draft,
            'severity' => AnnouncementSeverity::Info,
            'is_pinned' => false,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (): array => ['status' => AnnouncementStatus::Draft]);
    }

    public function published(): self
    {
        return $this->state(fn (): array => [
            'status' => AnnouncementStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (): array => ['status' => AnnouncementStatus::Archived]);
    }

    public function pinned(): self
    {
        return $this->state(fn (): array => ['is_pinned' => true]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }
}
