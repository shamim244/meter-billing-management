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
        // 1. Add owner_user_id to coupon_codes table
        Schema::table('coupon_codes', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('type')->constrained('users')->nullOnDelete();
            $table->index(['owner_user_id', 'type']);
        });

        // 2. Track referral signups (links referee to referrer upon registration)
        Schema::create('referral_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('referral_coupon_code_id')->nullable()->constrained('coupon_codes')->nullOnDelete();
            $table->dateTime('signed_up_at');
            $table->timestamps();

            $table->index(['referrer_user_id', 'signed_up_at']);
        });

        // 3. Referral Payouts Ledger
        Schema::create('referral_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_coupon_code_id')->nullable()->constrained('coupon_codes')->nullOnDelete();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('qualifying_payment_reference_type'); // 'subscription_payment' | 'topup'
            $table->string('qualifying_payment_reference_id');
            $table->decimal('reward_amount', 10, 2);
            $table->string('status')->default('pending'); // 'pending' | 'paid' | 'cancelled' | 'clawed_back'
            $table->dateTime('hold_expires_at');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('clawed_back_at')->nullable();
            $table->string('clawback_reason')->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->timestamps();

            $table->index(['referrer_user_id', 'status']);
            $table->index('referee_user_id');
            $table->index(['qualifying_payment_reference_type', 'qualifying_payment_reference_id'], 'idx_payout_payment_ref');
            $table->index(['status', 'hold_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_payouts');
        Schema::dropIfExists('referral_signups');

        Schema::table('coupon_codes', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropIndex(['owner_user_id', 'type']);
            $table->dropColumn('owner_user_id');
        });
    }
};
