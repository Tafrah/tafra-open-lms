<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * محاولات الطالب وإجاباته — `online_passed_exams` و`student_exam_answers` في طفرة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_passed_exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->foreignId('online_exam_id');
            TenantBlueprint::constrain($table, 'online_exam_id', 'online_exams', cascade: true);
            $table->unsignedBigInteger('bootcamp_section_item_id')->nullable();
            $table->unsignedInteger('current_student_chance')->default(1);
            $table->decimal('student_total_mark', 8, 2)->default(0);
            $table->decimal('max_total_mark', 8, 2)->default(0);
            $table->decimal('student_percentage', 5, 2)->default(0);
            $table->boolean('finished')->default(false);
            $table->string('grading_status', 16)->default('graded');   // pending | graded
            $table->json('question_order')->nullable();                 // ترتيب الأسئلة للمحاولة دي
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'student_id', 'online_exam_id']);
            $table->index(['tenant_id', 'grading_status', 'finished']);
        });

        Schema::create('student_exam_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('online_passed_exam_id');
            TenantBlueprint::constrain($table, 'online_passed_exam_id', 'online_passed_exams', cascade: true);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->foreignId('question_id');
            TenantBlueprint::constrain($table, 'question_id', 'questions', cascade: true);
            $table->json('answer')->nullable();               // {choice_id} أو {text}
            $table->boolean('is_correct')->nullable();
            $table->decimal('student_awarded_mark', 6, 2)->default(0);
            $table->decimal('max_question_mark', 6, 2)->default(0);
            $table->string('grading_status', 16)->default('auto');      // auto | pending | graded
            $table->foreignId('graded_by')->nullable();
            TenantBlueprint::constrain($table, 'graded_by', 'users');
            $table->timestamp('graded_at')->nullable();
            $table->text('grader_feedback')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'online_passed_exam_id', 'question_id'], 'exam_answers_attempt_question_unique');
            $table->index(['tenant_id', 'grading_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_exam_answers');
        Schema::dropIfExists('online_passed_exams');
    }
};
