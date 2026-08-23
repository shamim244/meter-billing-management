<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->string('last_working_reading', 50)->nullable()->after('status');
            $table->unsignedTinyInteger('last_working_month')->nullable()->after('last_working_reading');
            $table->unsignedSmallInteger('last_working_year')->nullable()->after('last_working_month');
            $table->string('baseline_previous_reading', 50)->nullable()->after('last_working_year');
        });
    }

    public function down(): void
    {
        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'last_working_reading',
                'last_working_month',
                'last_working_year',
                'baseline_previous_reading',
            ]);
        });
    }
};
