<?php

declare(strict_types=1);

namespace Src\Contexts\Tenancy\Infrastructure\Seeders;

use Illuminate\Database\Seeder;

/**
 * البيانات الافتراضية لكل مؤسسة جديدة.
 *
 * بيتشغّل من `tenants:seed` داخل قاعدة المؤسسة (multi) أو بسياقها
 * (single) — عن طريق `ProvisionTenantAction` (docs/24 بند ٣).
 *
 * لازم يكون **idempotent**: التجهيز ممكن يتعاد لو خطوة فشلت.
 * أي سياق محتاج بيانات افتراضية لمؤسسة بيضيف seeder هنا في `$seeders`.
 */
final class TenantDefaultsSeeder extends Seeder
{
    /** @var list<class-string<Seeder>> */
    private array $seeders = [];

    public function run(): void
    {
        foreach ($this->seeders as $seeder) {
            $this->call($seeder);
        }
    }
}
