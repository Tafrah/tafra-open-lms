<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('completed_steps')->nullable();
            $table->json('completed_tours')->nullable();
            $table->boolean('checklist_dismissed')->default(false);
            $table->timestamp('wizard_completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_progress');
    }
};
