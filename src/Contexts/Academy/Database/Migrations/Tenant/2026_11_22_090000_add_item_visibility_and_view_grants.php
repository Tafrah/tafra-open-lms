<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * - مواعيد ظهور العنصر للطلبة (`visible_from` / `visible_until`) — المدرس بيشوفه دايمًا.
 * - «مسموح بالتنزيل» لدرس الـ PDF (الافتراضي لأ).
 * - استثناء مشاهدات لطالب واحد في عنصر واحد: بيتزوّد على `allowed_views_number`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bootcamp_section_items', function (Blueprint $table): void {
            $table->dateTime('visible_from')->nullable()->after('is_free_preview');
            $table->dateTime('visible_until')->nullable()->after('visible_from');
        });

        Schema::table('online_lessons', function (Blueprint $table): void {
            $table->boolean('allow_download')->default(false)->after('number_of_pages');
        });

        Schema::create('student_item_view_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->foreignId('bootcamp_section_item_id');
            TenantBlueprint::constrain($table, 'bootcamp_section_item_id', 'bootcamp_section_items', cascade: true);
            $table->unsignedInteger('extra_views')->default(0);
            $table->foreignId('granted_by')->nullable();
            TenantBlueprint::constrain($table, 'granted_by', 'users');
            $table->timestamps();

            $table->unique(['tenant_id', 'student_id', 'bootcamp_section_item_id'], 'view_grants_student_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_item_view_grants');

        Schema::table('online_lessons', function (Blueprint $table): void {
            $table->dropColumn('allow_download');
        });

        Schema::table('bootcamp_section_items', function (Blueprint $table): void {
            $table->dropColumn(['visible_from', 'visible_until']);
        });
    }
};
