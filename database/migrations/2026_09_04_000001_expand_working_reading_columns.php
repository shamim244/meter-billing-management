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
            $table->string('working_reading', 191)->nullable()->change();
        });

        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->string('last_working_reading', 191)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_records', function (Blueprint $table) {
            $table->string('working_reading', 30)->nullable()->change();
        });

        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->string('last_working_reading', 50)->nullable()->change();
        });
    }
};
