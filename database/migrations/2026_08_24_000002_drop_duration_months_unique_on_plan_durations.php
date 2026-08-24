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
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Check if old unique index exists before attempting drop
            $uniqueIndexExists = collect(DB::select("
                SHOW INDEX FROM `plan_durations` 
                WHERE `Key_name` = 'plan_durations_plan_id_duration_months_unique'
            "))->isNotEmpty();

            if ($uniqueIndexExists) {
                // Ensure plan_id is indexed independently for the foreign key
                $planIdIndexExists = collect(DB::select("
                    SHOW INDEX FROM `plan_durations` 
                    WHERE `Key_name` = 'plan_durations_plan_id_index'
                "))->isNotEmpty();

                if (!$planIdIndexExists) {
                    Schema::table('plan_durations', function (Blueprint $table) {
                        $table->index('plan_id');
                    });
                }

                Schema::table('plan_durations', function (Blueprint $table) {
                    $table->dropUnique('plan_durations_plan_id_duration_months_unique');
                });
            }

            // Ensure duration_months is nullable
            Schema::table('plan_durations', function (Blueprint $table) {
                $table->unsignedSmallInteger('duration_months')->nullable()->default(1)->change();
            });
        } else {
            Schema::table('plan_durations', function (Blueprint $table) {
                $table->unsignedSmallInteger('duration_months')->nullable()->default(1)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op
    }
};
