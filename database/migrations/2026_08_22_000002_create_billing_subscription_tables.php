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
        // 1. Extend agent_subscriptions with lifecycle state machine fields
        Schema::table('agent_subscriptions', function (Blueprint $table) {
            $table->string('lifecycle_status', 30)->default('active')->after('status')->index(); // 'active', 'renewal_due', 'grace_period', 'suspended'
            $table->unsignedSmallInteger('grace_period_days')->default(3)->after('lifecycle_status');
            $table->timestamp('grace_period_ends_at')->nullable()->after('grace_period_days')->index();
            $table->boolean('auto_renewal_enabled')->default(false)->after('grace_period_ends_at');
            $table->timestamp('suspended_at')->nullable()->after('auto_renewal_enabled');
            $table->timestamp('last_state_change_at')->nullable()->after('suspended_at');
        });

        // 2. Add per-plan grace period override to plans table
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('grace_period_days')->nullable()->after('extra_consumer_rate');
        });

        // 3. Create renewal_attempts table for auto and manual renewal logging
        Schema::create('renewal_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_subscription_id')->constrained('agent_subscriptions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('attempt_type', 20)->index(); // 'manual', 'auto'
            $table->decimal('amount_charged', 10, 2);
            $table->string('wallet_transaction_id')->nullable()->index();
            $table->string('status', 30)->index(); // 'success', 'insufficient_balance', 'wallet_frozen', 'failed'
            $table->text('failure_reason')->nullable();
            $table->timestamp('attempted_at')->useCurrent()->index();
            $table->timestamps();
        });

        // 4. Create plan_upgrade_log table for proration math audit trail (both upgrades & downgrades)
        Schema::create('plan_upgrade_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_subscription_id')->constrained('agent_subscriptions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('to_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('action_type', 20)->index(); // 'upgrade', 'downgrade'
            $table->decimal('old_plan_credit', 10, 2);
            $table->decimal('new_plan_cost', 10, 2);
            $table->decimal('amount_charged', 10, 2); // positive for upgrade paid, adjustment credited for downgrade
            $table->string('wallet_transaction_id')->nullable()->index();
            $table->unsignedInteger('days_remaining_at_upgrade');
            $table->unsignedInteger('total_days_in_cycle');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_upgrade_log');
        Schema::dropIfExists('renewal_attempts');

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('grace_period_days');
        });

        Schema::table('agent_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'lifecycle_status',
                'grace_period_days',
                'grace_period_ends_at',
                'auto_renewal_enabled',
                'suspended_at',
                'last_state_change_at',
            ]);
        });
    }
};
