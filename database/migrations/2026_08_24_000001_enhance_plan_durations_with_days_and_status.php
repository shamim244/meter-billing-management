<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance plan_durations table
        Schema::table('plan_durations', function (Blueprint $table) {
            $table->string('duration_unit', 10)->default('month')->after('plan_id'); // 'month' or 'day'
            $table->unsignedInteger('duration_value')->default(1)->after('duration_unit'); // e.g. 7, 15, 1, 3, 12
            $table->boolean('is_active')->default(true)->after('extra_consumer_rate');
            $table->string('name')->nullable()->after('duration_value'); // e.g. "Weekly Trial", "1 Month Standard"
        });

        // Populate duration_value from existing duration_months
        DB::table('plan_durations')->update([
            'duration_unit' => 'month',
            'duration_value' => DB::raw('duration_months'),
        ]);

        // 2. Enhance agent_subscriptions table
        Schema::table('agent_subscriptions', function (Blueprint $table) {
            $table->string('duration_unit', 10)->default('month')->after('plan_id');
            $table->unsignedInteger('duration_value')->default(1)->after('duration_unit');
        });

        DB::table('agent_subscriptions')->update([
            'duration_unit' => 'month',
            'duration_value' => DB::raw('duration_months'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_durations', function (Blueprint $table) {
            $table->dropColumn(['duration_unit', 'duration_value', 'is_active', 'name']);
        });

        Schema::table('agent_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['duration_unit', 'duration_value']);
        });
    }
};
