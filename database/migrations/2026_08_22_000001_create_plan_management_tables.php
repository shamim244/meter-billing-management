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
        // 1. Master Plans Table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('included_mrus')->default(0);
            $table->unsignedInteger('included_consumers')->default(0);
            $table->decimal('extra_mru_rate', 10, 2)->default(0.00);
            $table->decimal('extra_consumer_rate', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('is_active');
        });

        // 2. Duration-Based Pricing Per Plan Table
        Schema::create('plan_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->unsignedSmallInteger('duration_months')->nullable()->default(1);
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->decimal('final_price', 10, 2);
            $table->decimal('extra_mru_rate', 10, 2)->nullable();
            $table->decimal('extra_consumer_rate', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['plan_id', 'duration_months']);
        });

        // 3. Locked Snapshot of Agent Subscriptions Table
        Schema::create('agent_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->unsignedSmallInteger('duration_months');
            $table->decimal('base_price_paid', 10, 2);
            $table->unsignedInteger('included_mrus_locked');
            $table->unsignedInteger('included_consumers_locked');
            $table->decimal('extra_mru_rate_locked', 10, 2);
            $table->decimal('extra_consumer_rate_locked', 10, 2);
            $table->timestamp('billing_start');
            $table->timestamp('billing_end');
            $table->string('status', 30)->default('active'); // active / renewal_due / grace_period / suspended / read_only
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // 4. Alter MRUs Table to Add Quota & Lock Tracking Columns
        Schema::table('mrus', function (Blueprint $table) {
            if (!Schema::hasColumn('mrus', 'locked_reason')) {
                $table->string('locked_reason', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('mrus', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('locked_reason');
            }
            if (!Schema::hasColumn('mrus', 'unlocked_at')) {
                $table->timestamp('unlocked_at')->nullable()->after('locked_at');
            }
            if (!Schema::hasColumn('mrus', 'is_over_quota')) {
                $table->boolean('is_over_quota')->default(false)->after('unlocked_at');
            }
        });

        // 5. Per-Cycle Consumer Quota Consumption Record Table
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mru_id')->constrained('mrus')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedSmallInteger('cycle_month');
            $table->unsignedSmallInteger('cycle_year');
            $table->unsignedInteger('consumer_count_at_creation')->default(0);
            $table->unsignedInteger('included_quota_used')->default(0);
            $table->unsignedInteger('extra_consumer_count')->default(0);
            $table->decimal('extra_consumer_charge', 10, 2)->default(0.00);
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->unique(['mru_id', 'cycle_month', 'cycle_year'], 'mru_cycle_unique');
            $table->index(['user_id', 'cycle_month', 'cycle_year']);
        });

        // 6. Plan Overage Charges Audit Table
        Schema::create('plan_overage_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('charge_type', 40); // 'mru_creation' | 'mru_renewal' | 'mru_unlock' | 'consumer_cycle'
            $table->string('reference_type', 40); // 'mru' | 'billing_cycle'
            $table->string('reference_id', 100);
            $table->decimal('amount', 10, 2);
            $table->string('wallet_transaction_id', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'charge_type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_overage_charges');
        Schema::dropIfExists('billing_cycles');

        Schema::table('mrus', function (Blueprint $table) {
            $table->dropColumn([
                'locked_reason',
                'locked_at',
                'unlocked_at',
                'is_over_quota',
            ]);
        });

        Schema::dropIfExists('agent_subscriptions');
        Schema::dropIfExists('plan_durations');
        Schema::dropIfExists('plans');
    }
};
