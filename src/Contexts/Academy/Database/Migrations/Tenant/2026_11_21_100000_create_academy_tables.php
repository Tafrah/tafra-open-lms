<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * المحتوى التعليمي — نفس أسماء جداول وأعمدة طفرة (Tafra-ESS-Laravel/database/migrations/tenant)
 * عشان التصدير والاستيراد يبقى نقل صفوف (docs/product/plan.html#sync).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educational_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->string('name');
            $table->string('code', 32);
            $table->string('color', 9)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'order']);
        });

        Schema::create('online_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('user_id')->nullable();
            TenantBlueprint::constrain($table, 'user_id', 'users');
            $table->string('name');
            $table->string('type', 32);                       // LessonType: YoutubeVideo | Pdf | Text
            $table->text('description')->nullable();
            $table->longText('text')->nullable();             // محتوى الدرس النصي (HTML منقّى)
            $table->string('external_url', 2048)->nullable(); // رابط يوتيوب
            $table->unsignedInteger('duration_in_seconds')->default(0);
            $table->unsignedInteger('number_of_pages')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('bootcamps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('user_id')->nullable();
            TenantBlueprint::constrain($table, 'user_id', 'users');
            $table->foreignId('educational_level_id')->nullable();
            TenantBlueprint::constrain($table, 'educational_level_id', 'educational_levels');
            $table->string('name');
            $table->string('slug');
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->string('promotional_video_url', 2048)->nullable();
            $table->string('publish_status', 16)->default('draft');
            $table->timestamp('publish_date')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('price_currency', 3)->default('EGP');
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->dateTime('discount_start_date')->nullable();
            $table->dateTime('discount_end_date')->nullable();
            $table->unsignedInteger('can_watch_in_days')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('featured_order')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'publish_status', 'educational_level_id']);
            $table->index(['tenant_id', 'is_featured', 'featured_order']);
        });

        Schema::create('bootcamp_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('bootcamp_id');
            TenantBlueprint::constrain($table, 'bootcamp_id', 'bootcamps', cascade: true);
            $table->string('name');
            $table->unsignedInteger('order')->default(0);
            $table->string('publish_status', 16)->default('published');
            $table->boolean('is_free_preview')->default(false);
            $table->unsignedBigInteger('prerequisite_exam_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'bootcamp_id', 'order']);
        });

        Schema::create('bootcamp_section_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('bootcamp_section_id');
            TenantBlueprint::constrain($table, 'bootcamp_section_id', 'bootcamp_sections', cascade: true);
            $table->string('name');
            $table->unsignedInteger('order')->default(0);
            $table->string('type', 16);                       // lesson | exam
            $table->foreignId('online_lesson_id')->nullable();
            TenantBlueprint::constrain($table, 'online_lesson_id', 'online_lessons');
            $table->unsignedBigInteger('online_exam_id')->nullable();
            $table->unsignedBigInteger('prerequisite_exam_id')->nullable();
            $table->unsignedInteger('allowed_views_number')->nullable();
            $table->string('publish_status', 16)->default('published');
            $table->boolean('is_free_preview')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'bootcamp_section_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamp_section_items');
        Schema::dropIfExists('bootcamp_sections');
        Schema::dropIfExists('bootcamps');
        Schema::dropIfExists('online_lessons');
        Schema::dropIfExists('educational_levels');
    }
};
