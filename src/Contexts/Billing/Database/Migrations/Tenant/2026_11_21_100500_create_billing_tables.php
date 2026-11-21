<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Src\Support\Infrastructure\Persistence\TenantBlueprint;

/**
 * الفلوس: طرق الدفع، الفواتير والإيصالات، المحفظة، الاشتراكات، أكواد الشحن، الكوبونات.
 * أسماء طفرة؛ `payment_methods` و`payment_receipts` خاصين بالمجاني (الدفع اليدوي بالإيصال).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->string('name');
            $table->string('type', 32);                        // vodafone_cash | instapay | bank_transfer | other
            $table->string('account_number', 64);
            $table->string('account_name')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active', 'order']);
        });

        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->string('code', 64);
            $table->string('name')->nullable();
            $table->string('type', 16);                        // percentage | fixed
            $table->decimal('value', 10, 2)->default(0);
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('max_per_user')->nullable()->default(1);
            $table->unsignedInteger('current_redemptions')->default(0);
            $table->string('applicable_type', 16)->default('all');   // all | specific
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            TenantBlueprint::constrain($table, 'created_by', 'users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('coupon_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('coupon_id');
            TenantBlueprint::constrain($table, 'coupon_id', 'coupons', cascade: true);
            $table->string('item_type', 32)->default('bootcamp');
            $table->unsignedBigInteger('item_id');
            $table->timestamps();

            $table->unique(['tenant_id', 'coupon_id', 'item_type', 'item_id']);
        });

        Schema::create('payment_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->string('number', 32);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->foreignId('bootcamp_id')->nullable();
            TenantBlueprint::constrain($table, 'bootcamp_id', 'bootcamps');
            $table->foreignId('coupon_id')->nullable();
            TenantBlueprint::constrain($table, 'coupon_id', 'coupons');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->string('payment_gateway', 32);             // manual_transfer | wallet | recharge_card | free
            $table->string('status', 16)->default('pending');  // pending | paid | rejected | canceled
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            TenantBlueprint::constrain($table, 'reviewed_by', 'users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['tenant_id', 'student_id']);
        });

        Schema::create('payment_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('payment_invoice_id');
            TenantBlueprint::constrain($table, 'payment_invoice_id', 'payment_invoices', cascade: true);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->foreignId('payment_method_id')->nullable();
            TenantBlueprint::constrain($table, 'payment_method_id', 'payment_methods');
            $table->decimal('amount', 10, 2);
            $table->string('sender_phone', 32)->nullable();
            $table->string('transfer_reference', 64)->nullable();
            $table->string('status', 16)->default('pending');  // pending | approved | rejected
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at']);
        });

        Schema::create('recharge_card_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->string('name');
            $table->string('type', 16);                        // wallet | bootcamp
            $table->decimal('amount', 10, 2)->default(0);
            $table->foreignId('bootcamp_id')->nullable();
            TenantBlueprint::constrain($table, 'bootcamp_id', 'bootcamps');
            $table->unsignedInteger('cards_count')->default(0);
            $table->timestamp('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('creator_id')->nullable();
            TenantBlueprint::constrain($table, 'creator_id', 'users');
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('recharge_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('recharge_card_group_id');
            TenantBlueprint::constrain($table, 'recharge_card_group_id', 'recharge_card_groups', cascade: true);
            $table->string('code', 32);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->foreignId('bootcamp_id')->nullable();      // كارت محاضرة (مش موجود في طفرة لسه)
            TenantBlueprint::constrain($table, 'bootcamp_id', 'bootcamps');
            $table->boolean('is_active')->default(true);
            $table->foreignId('used_by')->nullable();
            TenantBlueprint::constrain($table, 'used_by', 'students');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'recharge_card_group_id', 'used_at']);
        });

        Schema::create('money_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->uuid('uuid');
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->decimal('amount', 12, 2);                  // موجب دايمًا — الاتجاه في type
            $table->string('currency', 3)->default('EGP');
            $table->string('type', 16);                        // deposit | withdraw
            $table->string('source', 32);                      // recharge_card | purchase | admin | refund
            $table->nullableMorphs('transactionable');
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            TenantBlueprint::constrain($table, 'created_by', 'users');
            $table->timestamps();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'student_id', 'created_at']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('coupon_id');
            TenantBlueprint::constrain($table, 'coupon_id', 'coupons', cascade: true);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->foreignId('payment_invoice_id')->nullable();
            TenantBlueprint::constrain($table, 'payment_invoice_id', 'payment_invoices');
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('original_amount', 10, 2);
            $table->decimal('final_amount', 10, 2);
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'coupon_id', 'student_id']);
        });

        Schema::create('bootcamp_enrolled_students_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            TenantBlueprint::constrain($table, 'tenant_id', 'tenants', cascade: true);
            $table->foreignId('bootcamp_id');
            TenantBlueprint::constrain($table, 'bootcamp_id', 'bootcamps', cascade: true);
            $table->foreignId('student_id');
            TenantBlueprint::constrain($table, 'student_id', 'students', cascade: true);
            $table->string('type', 16)->default('enroll');     // enroll | unenroll
            $table->string('source', 32);                      // receipt | wallet | recharge_card | admin | free
            $table->foreignId('payment_invoice_id')->nullable();
            TenantBlueprint::constrain($table, 'payment_invoice_id', 'payment_invoices');
            $table->foreignId('recharge_card_id')->nullable();
            TenantBlueprint::constrain($table, 'recharge_card_id', 'recharge_cards');
            $table->foreignId('enrolled_by')->nullable();
            TenantBlueprint::constrain($table, 'enrolled_by', 'users');
            $table->unsignedInteger('access_duration_days')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'student_id', 'bootcamp_id', 'created_at'], 'enroll_hist_lookup_idx');
            $table->index(['tenant_id', 'bootcamp_id', 'type'], 'enroll_hist_bootcamp_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamp_enrolled_students_histories');
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('money_transactions');
        Schema::dropIfExists('recharge_cards');
        Schema::dropIfExists('recharge_card_groups');
        Schema::dropIfExists('payment_receipts');
        Schema::dropIfExists('payment_invoices');
        Schema::dropIfExists('coupon_items');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('payment_methods');
    }
};
