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
                if (! Schema::hasColumn('consumer_accounts', 'custom_avg_units')) {
                    $table->integer('custom_avg_units')->nullable()->after('custom_safe_ceiling');
                }
                if (! Schema::hasColumn('consumer_accounts', 'is_avg_excluded')) {
                    $table->boolean('is_avg_excluded')->default(false)->after('custom_avg_units');
                }
                if (! Schema::hasColumn('consumer_accounts', 'avg_exclude_reason')) {
                    $table->string('avg_exclude_reason', 100)->nullable()->after('is_avg_excluded');
                }
            });
        }

        if (Schema::hasTable('bill_records')) {
            Schema::table('bill_records', function (Blueprint $table) {
                if (! Schema::hasColumn('bill_records', 'custom_avg_units')) {
                    $table->integer('custom_avg_units')->nullable()->after('gap_action_applied');
                }
                if (! Schema::hasColumn('bill_records', 'is_avg_excluded')) {
                    $table->boolean('is_avg_excluded')->default(false)->after('custom_avg_units');
                }
                if (! Schema::hasColumn('bill_records', 'avg_exclude_reason')) {
                    $table->string('avg_exclude_reason', 100)->nullable()->after('is_avg_excluded');
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
                $columns = ['custom_avg_units', 'is_avg_excluded', 'avg_exclude_reason'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('consumer_accounts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('bill_records')) {
            Schema::table('bill_records', function (Blueprint $table) {
                $columns = ['custom_avg_units', 'is_avg_excluded', 'avg_exclude_reason'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('bill_records', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
