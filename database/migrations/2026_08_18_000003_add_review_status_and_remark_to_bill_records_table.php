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
            $table->string('review_status', 20)->default('pending')->after('working_reading');
            $table->string('remark', 255)->nullable()->after('review_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_records', function (Blueprint $table) {
            $table->dropColumn(['review_status', 'remark']);
        });
    }
};
