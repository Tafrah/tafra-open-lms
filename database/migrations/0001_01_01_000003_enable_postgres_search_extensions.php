<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Filament بيبحث بـ `LIKE '%term%'` — وده بيتجاهل الفهرس العادي تماماً.
 * فهرس trigram هو اللي بيخلّي البحث ده سريع على Postgres (docs/08 بند ٥).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->onPostgres()) {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    }

    public function down(): void
    {
        // الامتداد ممكن يكون مستخدم من حاجات تانية — مابنشيلوش
    }

    private function onPostgres(): bool
    {
        return Schema::getConnection()->getDriverName() === 'pgsql';
    }
};
