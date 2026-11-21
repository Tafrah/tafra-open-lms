<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أعمدة stancl/tenancy + الإيقاف المؤقت.
 *
 * `data`: بيتخزّن فيه أي خاصية مش عمود صريح — منها بيانات قاعدة
 * المستأجر (`tenancy_db_name`) في وضع multi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->json('data')->nullable();
            // `pending` لحد ما التجهيز يخلص فعلاً — لو السلسلة وقفت بعد
            // CreateDatabase، المؤسسة ماتبانش «جاهزة» (docs/24 بند ٣)
            $table->string('provisioning_status')->default('pending');
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();

            $table->index('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['data', 'provisioning_status', 'suspended_at', 'suspension_reason']);
        });
    }
};
