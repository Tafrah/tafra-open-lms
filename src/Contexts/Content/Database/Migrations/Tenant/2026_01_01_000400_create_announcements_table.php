<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('author_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            TenantBlueprint::constrain($table, 'author_id', 'users');
            $table->json('title');                    // مترجم
            $table->json('body');                     // مترجم
            $table->string('status')->default('draft');
            $table->string('severity')->default('info');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // فهارس: المستأجر أولاً دايماً
            $table->index(['tenant_id', 'status', 'published_at']);
            $table->index(['tenant_id', 'is_pinned']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
