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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_wallet_frozen')->default(false)->after('status');
            $table->text('wallet_frozen_reason')->nullable()->after('is_wallet_frozen');
            $table->timestamp('wallet_frozen_at')->nullable()->after('wallet_frozen_reason');
            $table->foreignId('wallet_frozen_by')->nullable()->after('wallet_frozen_at')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['wallet_frozen_by']);
            $table->dropColumn([
                'is_wallet_frozen',
                'wallet_frozen_reason',
                'wallet_frozen_at',
                'wallet_frozen_by',
            ]);
        });
    }
};
