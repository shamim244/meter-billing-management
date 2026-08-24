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
        Schema::table('plan_durations', function (Blueprint $table) {
            // 1. Add index on plan_id so foreign key requirement is satisfied
            $table->index('plan_id');
        });

        Schema::table('plan_durations', function (Blueprint $table) {
            // 2. Drop the old unique composite index
            try {
                $table->dropUnique('plan_durations_plan_id_duration_months_unique');
            } catch (\Throwable $e) {
                // Ignore if not exists
            }

            // 3. Make duration_months nullable
            $table->unsignedSmallInteger('duration_months')->nullable()->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_durations', function (Blueprint $table) {
            try {
                $table->unique(['plan_id', 'duration_months']);
            } catch (\Throwable $e) {
                //
            }
        });
    }
};
