<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «إظهار الإجابات» بقى ٣ حالات: لأ · بعد التسليم · بعد تاريخ معيّن.
 * `show_correct_answers_after_finish` لسه المفتاح الأساسي (عمود طفرة)،
 * و`show_answers_at` لو متحدد = الإجابات مابتظهرش قبله.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_exams', function (Blueprint $table): void {
            $table->timestamp('show_answers_at')->nullable()->after('show_correct_answers_after_finish');
        });
    }

    public function down(): void
    {
        Schema::table('online_exams', function (Blueprint $table): void {
            $table->dropColumn('show_answers_at');
        });
    }
};
