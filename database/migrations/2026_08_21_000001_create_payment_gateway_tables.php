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
        // 1. Master payment record
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('mode'); // 'pg' | 'manual_upi' | 'bank_transfer'
            $table->string('purpose'); // 'wallet_topup' | 'direct_subscription'
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('INR');
            $table->string('status')->default('pending'); // 'pending' | 'pending_verification' | 'success' | 'failed' | 'rejected'
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('utr_number')->nullable()->index();
            $table->string('screenshot_url')->nullable();
            $table->string('bank_reference')->nullable()->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('mode');
            $table->index('purpose');
            $table->index('created_at');
        });

        // 2. Recurring mandate info (for PG auto-debit)
        Schema::create('payment_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('gateway_mandate_id')->index();
            $table->string('status')->default('active'); // 'active' | 'paused' | 'cancelled' | 'failed'
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 3. Admin action audit trail
        Schema::create('payment_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // 'approved' | 'rejected' | 'refunded'
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('payment_id');
            $table->index('admin_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_audit_log');
        Schema::dropIfExists('payment_mandates');
        Schema::dropIfExists('payments');
    }
};
