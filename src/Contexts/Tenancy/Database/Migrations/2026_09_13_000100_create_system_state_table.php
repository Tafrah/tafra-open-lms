<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حالة النظام على مستوى التثبيت كله — أول قيمة فيها وضع تعدد
 * المستأجرين المقفول (docs/03 بند ١).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_state', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_state');
    }
};
