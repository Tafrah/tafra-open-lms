<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * ربط تطبيق ذكاء اصطناعي (عميل OAuth) بمستخدم — في مؤسسة واحدة بالظبط.
 *
 * مركزي في الوضعين زي جداول Passport: التوكن بيتفحص قبل ما نعرف المؤسسة،
 * فالربط لازم يتقرا من القاعدة المركزية (docs/23 بند ٤).
 *
 * `tenant_id = null` معناه الربط على اللوحة المركزية (فريق التشغيل) —
 * مقفول افتراضياً بـ `ai.central_enabled`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('oauth_client_id')->constrained('oauth_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('access', 16)->default('read');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // عميل واحد = مؤسسة واحدة للمستخدم. موافقة جديدة على مؤسسة تانية
            // بتبدّل الربط وبتلغي توكنز القديمة (AuthorizeAiConnectionAction)
            $table->unique(['user_id', 'oauth_client_id']);
            $table->index(['tenant_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_connections');
    }
};
