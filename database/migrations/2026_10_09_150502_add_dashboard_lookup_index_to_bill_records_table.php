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
            $table->index(['user_id', 'mru_id', 'billing_year', 'billing_month'], 'idx_bill_records_user_mru_year_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_records', function (Blueprint $table) {
            $table->dropIndex('idx_bill_records_user_mru_year_month');
        });
    }
};
