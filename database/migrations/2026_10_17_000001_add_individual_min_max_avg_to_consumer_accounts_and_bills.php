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
                if (! Schema::hasColumn('consumer_accounts', 'min_avg_units')) {
                    $table->integer('min_avg_units')->nullable()->after('custom_avg_units');
                }
                if (! Schema::hasColumn('consumer_accounts', 'max_avg_units')) {
                    $table->integer('max_avg_units')->nullable()->after('min_avg_units');
                }
            });
        }

        if (Schema::hasTable('bill_records')) {
            Schema::table('bill_records', function (Blueprint $table) {
                if (! Schema::hasColumn('bill_records', 'min_avg_units')) {
                    $table->integer('min_avg_units')->nullable()->after('custom_avg_units');
                }
                if (! Schema::hasColumn('bill_records', 'max_avg_units')) {
                    $table->integer('max_avg_units')->nullable()->after('min_avg_units');
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
                $cols = ['min_avg_units', 'max_avg_units'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('consumer_accounts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('bill_records')) {
            Schema::table('bill_records', function (Blueprint $table) {
                $cols = ['min_avg_units', 'max_avg_units'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('bill_records', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
