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
        Schema::table('bill_records', function (Blueprint $table) {
            if (!Schema::hasColumn('bill_records', 'reading_source')) {
                $table->string('reading_source', 20)->default('auto')->after('working_reading');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_records', function (Blueprint $table) {
            if (Schema::hasColumn('bill_records', 'reading_source')) {
                $table->dropColumn('reading_source');
            }
        });
    }
};
