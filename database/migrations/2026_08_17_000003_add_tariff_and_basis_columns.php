<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->string('tariff_category', 50)->nullable()->after('meter_no');
            $table->string('father_name', 255)->nullable()->after('consumer_name');
        });

        Schema::table('bill_records', function (Blueprint $table) {
            $table->string('tariff_category', 50)->nullable()->after('meter_no');
            $table->string('billing_basis', 20)->nullable()->after('tariff_category');
            $table->date('due_date')->nullable()->after('bill_date');

            $table->index(['user_id', 'tariff_category']);
            $table->index(['user_id', 'billing_basis']);
        });
    }

    public function down(): void
    {
        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->dropColumn(['tariff_category', 'father_name']);
        });

        Schema::table('bill_records', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'tariff_category']);
            $table->dropIndex(['user_id', 'billing_basis']);
            $table->dropColumn(['tariff_category', 'billing_basis', 'due_date']);
        });
    }
};
