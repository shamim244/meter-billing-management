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
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->unique(['coupon_code_id', 'redeemed_for_reference_id'], 'uq_coupon_code_redemption_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->dropUnique('uq_coupon_code_redemption_ref');
        });
    }
};
