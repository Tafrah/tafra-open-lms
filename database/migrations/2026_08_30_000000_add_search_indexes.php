<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> اسم الفهرس => التعبير */
    private const INDEXES = [
        'users_name_trgm_idx' => 'users USING gin (lower(name) gin_trgm_ops)',
        'users_email_trgm_idx' => 'users USING gin (lower(email) gin_trgm_ops)',
        'users_code_trgm_idx' => 'users USING gin (lower(code) gin_trgm_ops)',
        'tenants_slug_trgm_idx' => 'tenants USING gin (lower(slug) gin_trgm_ops)',
        // فهرس `announcements` اتنقل لميجريشن المستأجرين في سياق Content —
        // الجدول ده في قاعدة المؤسسة في وضع multi، مش هنا (docs/03 بند ٣)
    ];

    public function up(): void
    {
        if (! $this->onPostgres()) {
            return;
        }

        foreach (self::INDEXES as $name => $definition) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$name} ON {$definition}");
        }
    }

    public function down(): void
    {
        if (! $this->onPostgres()) {
            return;
        }

        foreach (array_keys(self::INDEXES) as $name) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }
    }

    private function onPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
