<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * سجل ملفات النقل اللي اتستوردت — نفس الملف مايتستوردش مرتين (كان بيكرر الكورسات والمحاولات).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->char('manifest_hash', 64);
            $table->string('source', 64)->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->foreignId('imported_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'manifest_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_imports');
    }
};
