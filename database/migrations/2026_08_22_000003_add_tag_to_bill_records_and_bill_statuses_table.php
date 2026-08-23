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
        if (Schema::hasTable('bill_records')) {
            Schema::table('bill_records', function (Blueprint $table) {
                if (!Schema::hasColumn('bill_records', 'tag')) {
                    $table->string('tag', 64)->default('OK')->index()->after('remark');
                }
            });
        }

        if (Schema::hasTable('bill_statuses')) {
            Schema::table('bill_statuses', function (Blueprint $table) {
                if (!Schema::hasColumn('bill_statuses', 'tag')) {
                    $table->string('tag', 64)->nullable()->index()->after('remark');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bill_records')) {
            Schema::table('bill_records', function (Blueprint $table) {
                if (Schema::hasColumn('bill_records', 'tag')) {
                    $table->dropColumn('tag');
                }
            });
        }

        if (Schema::hasTable('bill_statuses')) {
            Schema::table('bill_statuses', function (Blueprint $table) {
                if (Schema::hasColumn('bill_statuses', 'tag')) {
                    $table->dropColumn('tag');
                }
            });
        }
    }
};
