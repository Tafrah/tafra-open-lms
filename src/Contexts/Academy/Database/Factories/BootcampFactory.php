<?php

declare(strict_types=1);

namespace Src\Contexts\Academy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Contexts\Academy\Domain\Enums\PublishStatus;
use Src\Contexts\Academy\Domain\Models\Bootcamp;

/** @extends Factory<Bootcamp> */
final class BootcampFactory extends Factory
{
    protected $model = Bootcamp::class;

    public function definition(): array
    {
        return [
            'name' => $name = $this->faker->sentence(3),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'short_description' => $this->faker->sentence(10),
            'description' => '<p>'.$this->faker->paragraph().'</p>',
            'publish_status' => PublishStatus::Published,
            'price' => 150,
            'price_currency' => 'EGP',
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (): array => ['publish_status' => PublishStatus::Draft]);
    }

    public function free(): self
    {
        return $this->state(fn (): array => ['price' => 0]);
    }
}
