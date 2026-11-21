<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * الطالب بيدخل بالموبايل وكلمة السر على حارس `student` منفصل عن مستخدمي اللوحة.
 * في طفرة الطالب = صف في users (user_type=student) + صف في students — التصدير بيقسمه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->string('name');
            $table->string('phone', 20);
            $table->string('parent_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('code', 32);                        // كود الطالب
            $table->foreignId('educational_level_id')->nullable();
            TenantBlueprint::constrain($table, 'educational_level_id', 'educational_levels');
            $table->string('governorate', 64)->nullable();
            $table->string('school_type', 16)->nullable();     // general | azhar
            $table->json('extra_info')->nullable();
            $table->decimal('balance', 12, 2)->default(0);     // رصيد المحفظة (بيتحدّث مع money_transactions)
            $table->string('status', 16)->default('active');   // active | blocked
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'phone']);
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'educational_level_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
