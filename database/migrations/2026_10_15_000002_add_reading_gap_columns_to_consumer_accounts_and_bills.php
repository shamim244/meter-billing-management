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
        if (Schema::hasTable('consumer_accounts')) {
            Schema::table('consumer_accounts', function (Blueprint $table) {
                if (! Schema::hasColumn('consumer_accounts', 'last_physical_reading')) {
                    $table->string('last_physical_reading', 50)->nullable()->after('last_working_reading');
                }
                if (! Schema::hasColumn('consumer_accounts', 'last_inspected_at')) {
                    $table->timestamp('last_inspected_at')->nullable()->after('last_physical_reading');
                }
                if (! Schema::hasColumn('consumer_accounts', 'inspected_by_user_id')) {
                    $table->unsignedBigInteger('inspected_by_user_id')->nullable()->after('last_inspected_at');
                }
                if (! Schema::hasColumn('consumer_accounts', 'gap_recovery_mode')) {
                    $table->string('gap_recovery_mode', 20)->default('auto')->after('inspected_by_user_id');
                }
                if (! Schema::hasColumn('consumer_accounts', 'custom_safe_ceiling')) {
                    $table->integer('custom_safe_ceiling')->nullable()->after('gap_recovery_mode');
                }
            });
        }

        if (Schema::hasTable('bill_records')) {
            Schema::table('bill_records', function (Blueprint $table) {
                if (! Schema::hasColumn('bill_records', 'physical_reading')) {
                    $table->string('physical_reading', 50)->nullable()->after('working_reading');
                }
                if (! Schema::hasColumn('bill_records', 'reading_gap')) {
                    $table->integer('reading_gap')->nullable()->after('physical_reading');
                }
                if (! Schema::hasColumn('bill_records', 'gap_slice_applied')) {
                    $table->integer('gap_slice_applied')->nullable()->after('reading_gap');
                }
                if (! Schema::hasColumn('bill_records', 'gap_action_applied')) {
                    $table->string('gap_action_applied', 30)->nullable()->after('gap_slice_applied');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('consumer_accounts')) {
            Schema::table('consumer_accounts', function (Blueprint $table) {
                $columns = ['last_physical_reading', 'last_inspected_at', 'inspected_by_user_id', 'gap_recovery_mode', 'custom_safe_ceiling'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('consumer_accounts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('bill_records')) {
            Schema::table('bill_records', function (Blueprint $table) {
                $columns = ['physical_reading', 'reading_gap', 'gap_slice_applied', 'gap_action_applied'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('bill_records', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
