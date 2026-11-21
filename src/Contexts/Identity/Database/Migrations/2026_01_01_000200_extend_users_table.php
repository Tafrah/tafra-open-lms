<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('code')->nullable()->after('id');
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('status', 32)->default('active')->after('phone');
            $table->boolean('is_active')->default(true)->after('status');
            $table->string('locale', 5)->nullable()->after('is_active');
            $table->string('timezone', 64)->nullable()->after('locale');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->softDeletes();

            $table->unique('code');
            $table->index(['status', 'is_active']);
            $table->index('last_login_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropIndex(['status', 'is_active']);
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['created_at']);
            $table->dropColumn([
                'code', 'phone', 'status', 'is_active', 'locale', 'timezone',
                'last_login_at', 'last_login_ip', 'password_changed_at', 'deleted_at',
            ]);
        });
    }
};
