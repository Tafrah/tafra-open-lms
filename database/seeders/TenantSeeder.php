<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Contexts\Tenancy\Domain\Models\Tenant;

final class TenantSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tenants() as $attributes) {
            Tenant::query()->updateOrCreate(['slug' => $attributes['slug']], $attributes);
        }
    }

    /** @return list<array<string, mixed>> */
    private function tenants(): array
    {
        return [
            [
                'slug' => 'acme',
                'name' => ['ar' => 'شركة النموذج', 'en' => 'Acme Corporation'],
                'description' => ['ar' => 'مؤسسة تجريبية أولى', 'en' => 'First demo organization'],
                'primary_color' => '#419DF1',
                'is_active' => true,
            ],
            [
                'slug' => 'beta',
                'name' => ['ar' => 'مؤسسة بيتا', 'en' => 'Beta Organization'],
                'description' => ['ar' => 'مؤسسة تجريبية تانية', 'en' => 'Second demo organization'],
                'primary_color' => '#A32E3C',
                'is_active' => true,
            ],
        ];
    }
}
