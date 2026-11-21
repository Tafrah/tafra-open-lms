<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Contexts\Tenancy\Domain\Models\Tenant;
use Src\Support\Infrastructure\Theming\ThemeColorResolver;

/** @extends Factory<Tenant> */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $slug = Str::slug($this->faker->unique()->company());

        return [
            'slug' => $slug,
            'domain' => null,
            'name' => ['ar' => $this->faker->company(), 'en' => Str::headline($slug)],
            'description' => ['ar' => '', 'en' => ''],
            'primary_color' => ThemeColorResolver::DEFAULT_BRAND,
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
