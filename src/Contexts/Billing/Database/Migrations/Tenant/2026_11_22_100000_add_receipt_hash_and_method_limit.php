<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - `payment_receipts.file_hash`: بصمة sha256 لصورة الإيصال — نفس الصورة على فاتورتين = تحذير للمراجع.
 * - `payment_methods.monthly_limit`: حد الشهر للرقم (محافظ الموبايل ليها حد شهري) —
 *   لما المقبول في الشهر يوصله، الرقم بيختفي من صفحة الدفع.
 * الجدولين خاصين بالمجاني (docs/transfer/format.md)، فالأعمدة الجديدة مابتكسرش عقد طفرة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_receipts', function (Blueprint $table): void {
            $table->char('file_hash', 64)->nullable()->after('transfer_reference');

            $table->index(['tenant_id', 'file_hash'], 'payment_receipts_tenant_hash_index');
            $table->index(['tenant_id', 'transfer_reference'], 'payment_receipts_tenant_reference_index');
            $table->index(['tenant_id', 'sender_phone', 'amount'], 'payment_receipts_tenant_sender_index');
        });

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->decimal('monthly_limit', 12, 2)->nullable()->after('account_name');
        });
    }

    public function down(): void
    {
        Schema::table('payment_receipts', function (Blueprint $table): void {
            $table->dropIndex('payment_receipts_tenant_hash_index');
            $table->dropIndex('payment_receipts_tenant_reference_index');
            $table->dropIndex('payment_receipts_tenant_sender_index');
            $table->dropColumn('file_hash');
        });

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropColumn('monthly_limit');
        });
    }
};
