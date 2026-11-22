<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * أجهزة الطالب (docs/product/plan.html — «جهاز واحد لكل طالب»).
 *
 * الجهاز = كوكي فيها توكن عشوائي؛ القاعدة بتشيل الـ hash بس (sha256)، فتسريب
 * الجدول مايدّيش حد توكن يدخل بيه. المدرس بيحدد العدد المسموح من «إعدادات الموقع».
 * الجدول ده خاص بالنسخة المفتوحة — مش جزء من صيغة النقل لطفرة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->string('token_hash', 64);
            $table->string('device_name', 120)->nullable();   // «Chrome · Android»
            $table->string('user_agent', 512)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'student_id', 'token_hash']);
            $table->index(['tenant_id', 'student_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_devices');
    }
};
