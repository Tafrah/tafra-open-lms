<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * فهرس trigram لبحث الإعلانات (docs/21 بند ١١).
 *
 * كان في `database/migrations/..._add_search_indexes.php` مع فهارس
 * الجداول المركزية — وده كسر وضع multi: الميجريشن المركزية بتدوّر على
 * `announcements` في القاعدة المركزية، والجدول في قاعدة المؤسسة.
 * القاعدة: الفهرس بيعيش مع جدوله.
 *
 * `pg_trgm` بيتفعّل لكل قاعدة لوحدها — قاعدة المؤسسة الجديدة مابتورثهوش.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->onPostgres()) {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        // العنوان مترجم (JSON) — Filament بيبحث في النص الخام بتاعه
        DB::statement('CREATE INDEX IF NOT EXISTS announcements_title_trgm_idx ON announcements USING gin (lower(title::text) gin_trgm_ops)');
    }

    public function down(): void
    {
        if (! $this->onPostgres()) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS announcements_title_trgm_idx');
    }

    private function onPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
