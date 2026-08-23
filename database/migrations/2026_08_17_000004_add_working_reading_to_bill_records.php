<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_records', function (Blueprint $table) {
            $table->string('working_reading', 30)->nullable()->after('current_reading');
            $table->unsignedInteger('calculated_avg_units')->nullable()->after('units_consumed');
        });
    }

    public function down(): void
    {
        Schema::table('bill_records', function (Blueprint $table) {
            $table->dropColumn(['working_reading', 'calculated_avg_units']);
        });
    }
};
