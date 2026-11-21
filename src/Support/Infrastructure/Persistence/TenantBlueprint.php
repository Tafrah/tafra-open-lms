<?php

declare(strict_types=1);

namespace Src\Support\Infrastructure\Persistence;

use Illuminate\Database\Schema\Blueprint;
use Src\Support\Infrastructure\Tenancy\TenancyMode;

/**
 * قيود أعمدة جداول المستأجرين اللي بتشاور على جداول مركزية.
 *
 * في single: foreign key حقيقي زي أي جدول.
 * في multi: الجدول المركزي في **قاعدة تانية** — Postgres مابيقبلش FK
 * بين قواعد، فالعمود بيتعمل بفهرس بس، والتحقق في طبقة التطبيق
 * (docs/03 بند ٣).
 *
 * العمود نفسه بيتكتب سطر لوحده عشان larastan يستنتج خصائص الموديل منه
 * (بيقرا `$table->foreignId(...)` كجملة مستقلة بس)، والدالة دي بتضيف القيد:
 *
 *     $table->foreignId('tenant_id');
 *     TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
 *
 * الاختبار المعماري بيمنع `constrained()` و`foreign()` مباشر في `Database/Migrations/Tenant`.
 */
final class TenantBlueprint
{
    public static function constrain(
        Blueprint $table,
        string $column,
        string $centralTable,
        bool $cascade = false,
    ): void {
        if (! TenancyMode::sharesCentralDatabase()) {
            $table->index($column);

            return;
        }

        $foreign = $table->foreign($column)->references('id')->on($centralTable);

        if ($cascade) {
            $foreign->cascadeOnDelete();
        }
    }
}
