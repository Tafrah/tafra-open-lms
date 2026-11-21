<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * الامتحانات — أسماء طفرة. الفرق الوحيد: السؤال مربوط بالامتحان مباشرة (online_exam_id + order)
 * والتصدير بيولّد بلوك واحد لكل امتحان (online_exam_blocks / online_exam_block_items).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('user_id')->nullable();
            TenantBlueprint::constrain($table, 'user_id', 'users');
            $table->foreignId('educational_level_id')->nullable();
            TenantBlueprint::constrain($table, 'educational_level_id', 'educational_levels');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('allowed_chances')->nullable();      // null = مفتوح
            $table->unsignedInteger('duration_in_seconds')->nullable();  // null = من غير وقت
            $table->unsignedInteger('questions_number')->default(0);
            $table->decimal('min_percentage', 5, 2)->default(50);
            $table->boolean('published')->default(false);
            $table->boolean('show_correct_answers_after_finish')->default(true);
            $table->boolean('show_explanation')->default(true);
            $table->boolean('random_questions')->default(false);
            $table->boolean('random_choices')->default(false);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'published']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('user_id')->nullable();
            TenantBlueprint::constrain($table, 'user_id', 'users');
            $table->foreignId('online_exam_id')->nullable();
            TenantBlueprint::constrain($table, 'online_exam_id', 'online_exams', cascade: true);
            $table->unsignedInteger('order')->default(0);
            $table->string('type', 32);                        // multiple_choice | essay
            $table->text('text');                              // نص السؤال (HTML منقّى)
            $table->text('explanation_text')->nullable();
            $table->decimal('mark', 6, 2)->default(1);
            $table->unsignedTinyInteger('hardness')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'online_exam_id', 'order']);
        });

        Schema::create('choices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('question_id');
            TenantBlueprint::constrain($table, 'question_id', 'questions', cascade: true);
            $table->text('text');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'question_id', 'order']);
        });

        Schema::create('essay_question', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('question_id');
            TenantBlueprint::constrain($table, 'question_id', 'questions', cascade: true);
            $table->text('ideal_answer')->nullable();
            $table->boolean('allow_images')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('essay_question');
        Schema::dropIfExists('choices');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('online_exams');
    }
};
