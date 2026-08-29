<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Master Coupon Codes Table
        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('subscription_discount'); // 'subscription_discount' | 'topup_bonus'
            $table->string('discount_kind')->nullable(); // 'percentage' | 'flat'
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->foreignId('plan_restriction_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->unsignedInteger('usage_limit_per_user')->default(1);
            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedInteger('times_used_total')->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index('type');
        });

        // 2. Top-Up Bonus Slabs Table
        Schema::create('coupon_topup_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_code_id')->constrained('coupon_codes')->cascadeOnDelete();
            $table->decimal('min_amount', 10, 2);
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->decimal('bonus_percent', 5, 2);
            $table->timestamps();

            $table->index(['coupon_code_id', 'min_amount']);
        });

        // 3. Coupon Redemptions Audit Log
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_code_id')->constrained('coupon_codes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('redeemed_for_type'); // 'subscription_payment' | 'topup'
            $table->string('redeemed_for_reference_id')->nullable();
            $table->decimal('original_amount', 10, 2);
            $table->decimal('discount_or_bonus_amount', 10, 2);
            $table->decimal('final_amount', 10, 2);
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->dateTime('redeemed_at');
            $table->timestamps();

            $table->index(['coupon_code_id', 'user_id']);
            $table->index(['user_id', 'redeemed_for_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupon_topup_slabs');
        Schema::dropIfExists('coupon_codes');
    }
};
