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
        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->string('billing_basis', 20)->nullable()->default('OK')->after('tariff_category');
            $table->decimal('baseline_amount', 10, 2)->nullable()->default(0.00)->after('billing_basis');

            $table->index(['user_id', 'billing_basis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumer_accounts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'billing_basis']);
            $table->dropColumn(['billing_basis', 'baseline_amount']);
        });
    }
};
