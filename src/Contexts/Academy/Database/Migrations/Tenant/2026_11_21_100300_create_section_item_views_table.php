<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * تقدّم الطالب في عناصر الكورس — `section_item_views` في طفرة (student_id ← user_id في التصدير).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_item_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('bootcamp_section_item_id');
            TenantBlueprint::constrain($table, 'bootcamp_section_item_id', 'bootcamp_section_items', cascade: true);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->dateTime('viewed_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'student_id', 'bootcamp_section_item_id'], 'item_views_student_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_item_views');
    }
};
